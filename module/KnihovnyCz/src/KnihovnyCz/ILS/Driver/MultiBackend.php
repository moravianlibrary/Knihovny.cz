<?php

/**
 * Class MultiBackend
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020-2021.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\ILS\Driver;

use KnihovnyCz\Db\Table\InstConfigs;
use KnihovnyCz\Db\Table\InstSources;
use KnihovnyCz\ILS\Service\SolrIdResolver;
use VuFind\Auth\ILSAuthenticator;
use VuFind\Config\PluginManager as ConfigManager;
use VuFind\ILS\Driver\PluginManager;

/**
 * Class MultiBackend
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class MultiBackend extends \VuFind\ILS\Driver\MultiBackend
{
    /**
     * Table gateway to source configuration
     *
     * @var InstConfigs
     */
    protected $instConfigs;

    /**
     * Table gateway to institution source
     *
     * @var InstSources
     */
    protected $instSources;

    /**
     * Id resolver
     *
     * @var SolrIdResolver
     */
    protected SolrIdResolver $idResolver;

    /**
     * Constructor
     *
     * @param ConfigManager    $configLoader Configuration loader
     * @param ILSAuthenticator $ilsAuth      ILS authenticator
     * @param PluginManager    $dm           ILS driver manager
     * @param InstConfigs      $instConfigs  Instances configurations
     * @param InstSources      $instSources  Instances names
     * @param SolrIdResolver   $idResolver   Id resolver
     */
    public function __construct(
        ConfigManager $configLoader,
        ILSAuthenticator $ilsAuth, PluginManager $dm, InstConfigs $instConfigs,
        InstSources $instSources, SolrIdResolver $idResolver
    ) {
        $this->instConfigs = $instConfigs;
        $this->instSources = $instSources;
        $this->idResolver = $idResolver;
        parent::__construct($configLoader, $ilsAuth, $dm);
    }

    /**
     * Get configuration for the ILS driver.  We load the settings from DB.
     * If no settings found, we will return an empty array.
     *
     * @param string $source The source id to use for determining the
     * configuration
     *
     * @return array   The configuration of the driver
     */
    protected function getDriverConfig($source)
    {
        $instSource = $this->instSources->getSource($source);
        if ($instSource == null) {
            return [];
        }
        return $this->instConfigs->getConfig($instSource);
    }

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     * @param array $params Parameters
     *
     * @return mixed Array of the patron's transactions
     */
    public function getMyTransactions($patron, $params = [])
    {
        $data = parent::getMyTransactions($patron, $params);
        return $this->resolveIds($data, $patron);
    }

    /**
     * Get Patron Transaction History
     *
     * This is responsible for retrieving all historic transactions
     * (i.e. checked out items) by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     * @param array $params Retrieval params
     *
     * @return array        Array of the patron's transactions
     */
    public function getMyTransactionHistory($patron, $params)
    {
        $data = parent::getMyTransactionHistory($patron, $params);
        return $this->resolveIds($data, $patron);
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed      Array of the patron's holds
     */
    public function getMyHolds($patron)
    {
        $data = parent::getMyHolds($patron);
        return $this->resolveIds($data, $patron);
    }

    /**
     * Resolve identifiers
     *
     * @param array $data   Data from ILS driver
     * @param array $patron Patron data
     *
     * @return array Data with right identifiers
     */
    protected function resolveIds(array $data, array $patron): array
    {
        $source = $this->getSource($patron['cat_username']);
        $sourceConfig = $this->getDriverConfig($source);
        $config = $sourceConfig['IdResolver'] ?? [];
        return $this->idResolver->resolveIds($data, $config);
    }

    /**
     * Takes sigla and return library source for it
     *
     * @param string $sigla SIGLA library identifier
     *
     * @return string|null
     */
    public function siglaToSource($sigla): ?string
    {
        $siglaMapping = $this->config['SiglaMapping'] ?? [];
        $siglaMapping = array_flip($siglaMapping);
        return $siglaMapping[$sigla] ?? null;
    }

    /**
     * Library source to sigla
     *
     * @param string $source Library source identifier
     *
     * @return string|null
     */
    public function sourceToSigla(string $source): ?string
    {
        $siglaMapping = $this->config['SiglaMapping'] ?? [];
        return $siglaMapping[$source] ?? null;
    }
}
