<?php
/**
 * Knihovny.cz solr default record driver factory
 *
 * PHP version 7
 *
 * Copyright (C) The Moravian Library 2015-2019.
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
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
namespace KnihovnyCz\RecordDriver;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

/**
 * Class solr default record driver factory
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class SolrDefaultFactory extends \VuFind\RecordDriver\SolrDefaultFactory
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
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        /**
         * Record driver
         *
         * @var SolrDefault $driver
         */
        $driver = parent::__invoke($container, $requestedName, $options);
        $driver->attachRecordLoader($container->get(\VuFind\Record\Loader::class));
        $driver->attachLibraryIdMappings(
            $container->get(\VuFind\Config\PluginManager::class)
                ->get('MultiBackend')->LibraryIDMapping
        );
        $driver->attachGoogleService(
            $container->get(\KnihovnyCz\Service\GoogleBooksLinkService::class)
        );
        $driver->attachZboziService(
            $container->get(\KnihovnyCz\Service\ZboziLinkService::class)
        );
        $driver->attachObalkyKnihService(
            $container->get(\VuFind\Content\ObalkyKnihService::class)
        );
        $driver->attachAuthManager(
            $container->get('VuFind\AuthManager')
        );
        return $driver;
    }
}
