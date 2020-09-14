<?php

/**
 * Class MultiBackend
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
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
use VuFind\Auth\ILSAuthenticator;
use VuFind\ILS\Driver\PluginManager;

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
     * Constructor
     *
     * @param \VuFind\Config\PluginManager  $configLoader Configuration loader
     * @param ILSAuthenticator $ilsAuth      ILS authenticator
     * @param PluginManager                 $dm           ILS driver manager
     */
    public function __construct(\VuFind\Config\PluginManager $configLoader,
        ILSAuthenticator $ilsAuth, PluginManager $dm, InstConfigs $instConfigs, InstSources $instSources
    ) {
        $this->instConfigs = $instConfigs;
        $this->instSources = $instSources;
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

}
