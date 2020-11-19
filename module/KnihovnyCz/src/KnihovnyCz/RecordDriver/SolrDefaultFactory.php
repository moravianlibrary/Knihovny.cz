<?php

namespace KnihovnyCz\RecordDriver;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

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
        /** @var SolrDefault $driver */
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
        return $driver;
    }
}

