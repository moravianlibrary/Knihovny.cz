<?php

namespace KnihovnyCz\Ziskej;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Factory for instantiating objects
 */
class ZiskejEddFactory implements FactoryInterface
{
    /**
     * Create service
     * @param \Interop\Container\ContainerInterface $container
     * @param string                                $requestedName
     * @param array|null                            $options
     *
     * @return \KnihovnyCz\Ziskej\ZiskejEdd
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ZiskejEdd
    {
        /** @var \Laminas\Config\Config $config */
        $config = $container->get('VuFind\Config')->get('config');

        /** @var \VuFind\Cookie\CookieManager $cookieManager */
        $cookieManager = $container->get('VuFind\CookieManager');

        return new \KnihovnyCz\Ziskej\ZiskejEdd($config, $cookieManager);
    }
}
