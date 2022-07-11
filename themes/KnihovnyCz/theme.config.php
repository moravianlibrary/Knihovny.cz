<?php

use Psr\Container\ContainerInterface;

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
            KnihovnyCz\View\Helper\KnihovnyCz\RecordLinker::class => \KnihovnyCz\View\Helper\KnihovnyCz\RecordLinkerFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\GoogleTagManager::class => \KnihovnyCz\View\Helper\KnihovnyCz\GoogleTagManagerFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\GeoCoords::class => \VuFind\View\Helper\Root\GeoCoordsFactory::class,
        ],
        'invokables' => [
            \KnihovnyCz\View\Helper\KnihovnyCz\FooterLink::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\LibrariesApiLookfor::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\SplitText::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\EscapeElementId::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\TextFormatter::class,
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
            'recordLinker' => KnihovnyCz\View\Helper\KnihovnyCz\RecordLinker::class,
            'textFormatter' => KnihovnyCz\View\Helper\KnihovnyCz\TextFormatter::class,
            'geocoords' => \KnihovnyCz\View\Helper\KnihovnyCz\GeoCoords::class,
        ],
    ],
    'icons' => [
        'aliases' => [
            /**
             * Icons can be assigned or overriden here
             *
             * Format: 'icon' => [set:]icon[:extra_classes]
             * Icons assigned without set will use the defaultSet.
             * In order to specify extra CSS classes, you must also specify a set.
             *
             * All of the items below have been specified with FontAwesome to allow
             * for a strong inheritance safety net but this is not required.
             */
            // UI
            'back-to-record' => 'FontAwesome:long-arrow-left',
            'cart' => 'FontAwesome:suitcase:fa-fw',
            'cart-add' => 'FontAwesome:plus-circle',
            'cite' => 'FontAwesome:commenting-o',
            'currency-czk' => 'FontAwesome:money',
            'dropdown-caret' => 'FontAwesome:caret-down:fa-fw',
            'feedback' => 'FontAwesome:comments-o:fa-fw',
            'language-select' => 'FontAwesome:globe:fa-fw',
            'my-account' => 'FontAwesome:user-circle-o:fa-fw',
            'share-record' => 'FontAwesome:share-alt',
            'sign-in' => 'FontAwesome:sign-in:fa-fw',
            'sign-out' => 'FontAwesome:sign-out:fa-fw',
            'ui-menu' => 'FontAwesome:bars:fa-fw',
            'user-list-add' => 'FontAwesome:star',
            'user-ziskej' => 'FontAwesome:file-text:fa-fw',
        ],
    ],
    'favicon' => 'icon-knihovny.png',
];
