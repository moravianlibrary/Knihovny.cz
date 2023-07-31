<?php

use Psr\Container\ContainerInterface;

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
            KnihovnyCz\View\Helper\KnihovnyCz\SearchTabs::class => \KnihovnyCz\View\Helper\KnihovnyCz\SearchTabsFactory::class,
            KnihovnyCz\View\Helper\KnihovnyCz\RecordLinker::class => \KnihovnyCz\View\Helper\KnihovnyCz\RecordLinkerFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\GoogleTagManager::class => \KnihovnyCz\View\Helper\KnihovnyCz\GoogleTagManagerFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\GeoCoords::class => \VuFind\View\Helper\Root\GeoCoordsFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\SetupEmbeddedThemeResources::class =>
                KnihovnyCz\View\Helper\KnihovnyCz\SetupEmbeddedThemeResourcesFactory::class,
        ],
        'invokables' => [
            \KnihovnyCz\View\Helper\KnihovnyCz\FooterLink::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\LibrariesApiLookfor::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\SplitText::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\EscapeElementId::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\TextFormatter::class,
            KnihovnyCz\View\Helper\KnihovnyCz\UserCards::class,
            KnihovnyCz\View\Helper\KnihovnyCz\ContextHelp::class,
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
            'searchTabs' => KnihovnyCz\View\Helper\KnihovnyCz\SearchTabs::class,
            'recordLinker' => KnihovnyCz\View\Helper\KnihovnyCz\RecordLinker::class,
            'textFormatter' => KnihovnyCz\View\Helper\KnihovnyCz\TextFormatter::class,
            'geocoords' => \KnihovnyCz\View\Helper\KnihovnyCz\GeoCoords::class,
            'userCards' => KnihovnyCz\View\Helper\KnihovnyCz\UserCards::class,
            'contextHelp' => KnihovnyCz\View\Helper\KnihovnyCz\ContextHelp::class,
            'setupEmbeddedThemeResources' => KnihovnyCz\View\Helper\KnihovnyCz\SetupEmbeddedThemeResources::class,
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
            'authority-person' => 'FontAwesome:user-circle-o',
            'back-to-record' => 'FontAwesome:long-arrow-left',
            'back-to-results' => 'Alias:back-to-record',
            'cart' => 'FontAwesome:suitcase:fa-fw',
            'cart-add' => 'FontAwesome:plus-circle',
            'cite' => 'FontAwesome:commenting-o:fa-fw',
            'collapse-close' => 'FontAwesome:caret-up',
            'collapse-open' => 'FontAwesome:caret-down',
            'currency-czk' => 'FontAwesome:money',
            'dropdown-caret' => 'FontAwesome:caret-down:fa-fw',
            'export' => 'FontAwesome:external-link:fa-fw',
            'external-link' => 'FontAwesome:link:fa-fw',
            'facebook' => 'FontAwesome:facebook-square',
            'feedback' => 'FontAwesome:comments-o:fa-fw',
            'github' => 'FontAwesome:github-square',
            'home' => 'FontAwesome:home',
            'information' => 'FontAwesome:info-circle',
            'inspiration' => 'FontAwesome:compass',
            'inspiration-show-more' => 'FontAwesome:angle-right',
            'instagram' => 'FontAwesome:instagram',
            'language-select' => 'FontAwesome:globe:fa-fw',
            'library-general-info' => 'FontAwesome:circle-thin',
            'load-institution-filter' => 'FontAwesome:home:fa-fw',
            'map-marker' => 'FontAwesome:map-marker',
            'my-account' => 'FontAwesome:user-circle-o:fa-fw',
            'opening-hours' => 'FontAwesome:clock-o',
            'phone-number' => 'FontAwesome:phone',
            'regional-library-marker' => 'FontAwesome:dot-circle-o',
            'save-institution-filter' => 'FontAwesome:floppy-o:fa-fw',
            'send-email' => 'FontAwesome:envelope:fa-fw',
            'share-record' => 'FontAwesome:share-alt:fa-fw',
            'short-loans' => 'FontAwesome:calendar:fa-fw',
            'sign-in' => 'FontAwesome:sign-in:fa-fw',
            'sign-out' => 'FontAwesome:sign-out:fa-fw',
            'ui-menu' => 'FontAwesome:bars:fa-fw',
            'user-favorites' => 'FontAwesome:star',
            'user-list-add' => 'FontAwesome:star:fa-fw',
            'user-settings' => 'FontAwesome:gear:fa-fw',
            'user-ziskej' => 'FontAwesome:file-text:fa-fw',
            'user-ziskej-edd' => 'FontAwesome:file-text:fa-fw',
            'wantit' => 'FontAwesome:hand-paper-o',
            'website' => 'FontAwesome:globe',
            'ziskej-count-fee' => 'FontAwesome:refresh',
            'ziskej-done' => 'FontAwesome:check',
            'ziskej-download' => 'FontAwesome:download',
            'ziskej-order' => 'FontAwesome:shopping-cart',
            'ziskej-order-detail' => 'FontAwesome:search',
            'ziskej-order-messages' => 'FontAwesome:comments',
            'ziskej-waiting' => 'FontAwesome:times',
            'ziskej-warning' => 'FontAwesome:exclamation-triangle',
        ],
    ],
    'favicon' => 'icon-knihovny.png',
];
