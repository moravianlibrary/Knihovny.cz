<?php

use Interop\Container\ContainerInterface;

return [
    'extends' => 'bootstrap3',
    'helpers' => [
        'factories' => [
            KnihovnyCz\View\Helper\KnihovnyCz\HeadTitle::class => VuFind\View\Helper\Root\HeadTitleFactory::class,
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'KnihovnyCz\View\Helper\KnihovnyCz\RecordDataFormatterFactory',
            KnihovnyCz\View\Helper\KnihovnyCz\ZiskejMvs::class => function (ContainerInterface $container, $requestedName) {
                $dependency = $container->get(KnihovnyCz\Ziskej\ZiskejMvs::class);
                return new $requestedName($dependency);
            },
            KnihovnyCz\View\Helper\KnihovnyCz\ZiskejEdd::class => function (ContainerInterface $container, $requestedName) {
                $dependency = $container->get(KnihovnyCz\Ziskej\ZiskejEdd::class);
                return new $requestedName($dependency);
            },
            KnihovnyCz\View\Helper\KnihovnyCz\SearchTabs::class => \VuFind\View\Helper\Root\SearchTabsFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\GoogleTagManager::class => \KnihovnyCz\View\Helper\KnihovnyCz\GoogleTagManagerFactory::class,
        ],
        'invokables' => [
            \KnihovnyCz\View\Helper\KnihovnyCz\FooterLink::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\LibrariesApiLookfor::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\SplitText::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\EscapeElementId::class,
        ],
        'aliases' => [
            'footerLink' => \KnihovnyCz\View\Helper\KnihovnyCz\FooterLink::class,
            'recordDataFormatter' => 'VuFind\View\Helper\Root\RecordDataFormatter',
            'ziskejMvs' => KnihovnyCz\View\Helper\KnihovnyCz\ZiskejMvs::class,
            'ziskejEdd' => KnihovnyCz\View\Helper\KnihovnyCz\ZiskejEdd::class,
            'googleTagManager' => \KnihovnyCz\View\Helper\KnihovnyCz\GoogleTagManager::class,
            'librariesApiLookfor' => \KnihovnyCz\View\Helper\KnihovnyCz\LibrariesApiLookfor::class,
            'splitText' => \KnihovnyCz\View\Helper\KnihovnyCz\SplitText::class,
            'escapeElementId' => \KnihovnyCz\View\Helper\KnihovnyCz\EscapeElementId::class,
            'headTitle' => KnihovnyCz\View\Helper\KnihovnyCz\HeadTitle::class,
            'searchTabs' => KnihovnyCz\View\Helper\KnihovnyCz\SearchTabs::class,
        ],
    ],
    'favicon' => 'icon-knihovny.png',
];
