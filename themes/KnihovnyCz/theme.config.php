<?php

use Interop\Container\ContainerInterface;

return [
    'extends' => 'bootstrap3',
    'helpers' => [
        'factories' => [
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'KnihovnyCz\View\Helper\KnihovnyCz\RecordDataFormatterFactory',
            KnihovnyCz\View\Helper\KnihovnyCz\Ziskej::class => function(ContainerInterface $container, $requestedName) {
                $dependency = $container->get(KnihovnyCz\Ziskej\ZiskejMvs::class);
                return new KnihovnyCz\View\Helper\KnihovnyCz\Ziskej($dependency);
            },
            KnihovnyCz\View\Helper\KnihovnyCz\ZiskejEdd::class => function(ContainerInterface $container, $requestedName) {
                $dependency = $container->get(KnihovnyCz\Ziskej\ZiskejEdd::class);
                return new KnihovnyCz\View\Helper\KnihovnyCz\ZiskejEdd($dependency);
            },
        ],
        'aliases' => [
            'recordDataFormatter' => 'VuFind\View\Helper\Root\RecordDataFormatter',
            'Ziskej' => KnihovnyCz\View\Helper\KnihovnyCz\Ziskej::class,
            'ZiskejEdd' => KnihovnyCz\View\Helper\KnihovnyCz\ZiskejEdd::class,
        ],
    ],
    'favicon' => 'icon-knihovny.png',
];
