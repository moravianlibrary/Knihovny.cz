<?php

use Interop\Container\ContainerInterface;

return [
    'extends' => 'bootstrap3',
    'helpers' => [
        'factories' => [
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'KnihovnyCz\View\Helper\KnihovnyCz\RecordDataFormatterFactory',
            KnihovnyCz\View\Helper\KnihovnyCz\ZiskejMvs::class => function (ContainerInterface $container, $requestedName) {
                $dependency = $container->get(KnihovnyCz\Ziskej\ZiskejMvs::class);
                return new $requestedName($dependency);
            },
            KnihovnyCz\View\Helper\KnihovnyCz\ZiskejEdd::class => function (ContainerInterface $container, $requestedName) {
                $dependency = $container->get(KnihovnyCz\Ziskej\ZiskejEdd::class);
                return new $requestedName($dependency);
            },
        ],
        'invokables' => [
            \KnihovnyCz\View\Helper\KnihovnyCz\FooterLink::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\LibrariesApiLookfor::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\SplitText::class,
        ],
        'aliases' => [
            'footerLink' => \KnihovnyCz\View\Helper\KnihovnyCz\FooterLink::class,
            'recordDataFormatter' => 'VuFind\View\Helper\Root\RecordDataFormatter',
            'ZiskejMvs' => KnihovnyCz\View\Helper\KnihovnyCz\ZiskejMvs::class,
            'ZiskejEdd' => KnihovnyCz\View\Helper\KnihovnyCz\ZiskejEdd::class,
            'librariesApiLookfor' => \KnihovnyCz\View\Helper\KnihovnyCz\LibrariesApiLookfor::class,
            'splitText' => \KnihovnyCz\View\Helper\KnihovnyCz\SplitText::class,
        ],
    ],
    'favicon' => 'icon-knihovny.png',
];
