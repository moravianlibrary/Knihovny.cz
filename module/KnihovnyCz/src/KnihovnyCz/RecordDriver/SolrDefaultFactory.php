<?php

namespace KnihovnyCz\RecordDriver;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

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
        $driver = parent::__invoke($container, $requestedName, $options);
        $driver->attachRecordLoader($container->get(\VuFind\Record\Loader::class));
        return $driver;
    }
}

