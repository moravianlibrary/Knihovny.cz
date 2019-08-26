<?php

namespace KnihovnyCz\RecordDriver;

class SolrMarcFactory implements \Zend\ServiceManager\Factory\FactoryInterface
{
    public function __invoke(\Interop\Container\ContainerInterface $container, $requestedName, array $options = null)
    {
        return new \KnihovnyCz\RecordDriver\SolrMarc();
    }
}

