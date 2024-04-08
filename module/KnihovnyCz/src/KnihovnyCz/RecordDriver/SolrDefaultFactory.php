<?php

namespace KnihovnyCz\RecordDriver;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

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
        $driver->attachRecordLoader($container->get(\VuFind\Record\Loader::class));
        $driver->attachRecordFactory(
            $container->get('VuFind\RecordDriverPluginManager')
        );
        $multiBackend = $container->get(\VuFind\Config\PluginManager::class)
            ->get('MultiBackend');
        $driver->attachLibraryIdMappings($multiBackend->LibraryIDMapping);
        if (isset($multiBackend->LibraryAjaxStatus)) {
            $driver->attachLibraryAjaxStatus($multiBackend->LibraryAjaxStatus);
        }
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
