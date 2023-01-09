<?php
declare(strict_types=1);

/**
 * Class EdsFactory
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2022.
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\RecordDriver;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;
use VuFind\RecordDriver\NameBasedConfigFactory;

/**
 * Class EdsFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class EdsFactory extends NameBasedConfigFactory
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws \Exception if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        /**
         * Record driver
         *
         * @var SolrDefault $driver
         */
        $driver = parent::__invoke($container, $requestedName, $options);
        $driver->attachSparqlService(
            $container->get(\KnihovnyCz\Wikidata\SparqlService::class)
        );
        // Populate cache storage if a setCacheStorage method is present:
        if (method_exists($driver, 'setCacheStorage')) {
            $driver->setCacheStorage(
                $container->get(\VuFind\Cache\Manager::class)->getCache('object')
            );
        }
        return $driver;
    }
}
