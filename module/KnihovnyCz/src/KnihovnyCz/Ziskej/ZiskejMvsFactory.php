<?php

namespace KnihovnyCz\Ziskej;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Factory for instantiating objects
 */
class ZiskejMvsFactory implements FactoryInterface
{
    /**
     * Create service
     * @param \Interop\Container\ContainerInterface $container
     * @param string                                $requestedName
     * @param array|null                            $options
     *
     * @return \KnihovnyCz\Ziskej\ZiskejMvs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ZiskejMvs
    {
        /** @var \Laminas\Config\Config $config */
        $config = $container->get('VuFind\Config')->get('config');

        /** @var \VuFind\Cookie\CookieManager $cookieManager */
        $cookieManager = $container->get('VuFind\CookieManager');

        return new \KnihovnyCz\Ziskej\ZiskejMvs($config, $cookieManager);
    }
}
