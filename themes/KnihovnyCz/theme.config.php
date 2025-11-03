<?php

use Psr\Container\ContainerInterface;

return [
    'extends' => 'bootstrap5',
    'helpers' => [
        'factories' => [
            \KnihovnyCz\View\Helper\KnihovnyCz\AccountMenu::class => \VuFind\View\Helper\Root\AccountMenuFactory::class,
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
            \KnihovnyCz\View\Helper\KnihovnyCz\GeoCoords::class => \VuFind\View\Helper\Root\GeoCoordsFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\SetupEmbeddedThemeResources::class =>
                KnihovnyCz\View\Helper\KnihovnyCz\SetupEmbeddedThemeResourcesFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\DateTime::class =>
                \VuFind\View\Helper\Root\DateTimeFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\Notifications::class => \KnihovnyCz\View\Helper\KnihovnyCz\NotificationsFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\SearchMemory::class => \VuFind\View\Helper\Root\SearchMemoryFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\SocialLinks::class => \KnihovnyCz\View\Helper\KnihovnyCz\SocialLinksFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\GoogleTagManager::class => \VuFind\View\Helper\Root\GoogleTagManagerFactory::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\Palmknihy::class => \KnihovnyCz\View\Helper\KnihovnyCz\PalmknihyFactory::class,
            \Laminas\View\Helper\ServerUrl::class => \KnihovnyCz\View\Helper\KnihovnyCz\ServerUrlFactory::class,
        ],
        'invokables' => [
            \KnihovnyCz\View\Helper\KnihovnyCz\FooterLink::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\LibrariesApiLookfor::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\SplitText::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\EscapeElementId::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\TextFormatter::class,
            KnihovnyCz\View\Helper\KnihovnyCz\UserCards::class,
            KnihovnyCz\View\Helper\KnihovnyCz\ContextHelp::class,
            KnihovnyCz\View\Helper\KnihovnyCz\DatePicker::class,
            \KnihovnyCz\View\Helper\KnihovnyCz\ResultsCount::class,
        ],
        'aliases' => [
            'accountMenu' => \KnihovnyCz\View\Helper\KnihovnyCz\AccountMenu::class,
            'footerLink' => \KnihovnyCz\View\Helper\KnihovnyCz\FooterLink::class,
            'recordDataFormatter' => 'VuFind\View\Helper\Root\RecordDataFormatter',
            'ziskejMvs' => KnihovnyCz\View\Helper\KnihovnyCz\ZiskejMvs::class,
            'ziskejEdd' => KnihovnyCz\View\Helper\KnihovnyCz\ZiskejEdd::class,
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
            'dateTime' => \KnihovnyCz\View\Helper\KnihovnyCz\DateTime::class,
            'datePicker' => KnihovnyCz\View\Helper\KnihovnyCz\DatePicker::class,
            'resultsCount' => \KnihovnyCz\View\Helper\KnihovnyCz\ResultsCount::class,
            'notifications' => \KnihovnyCz\View\Helper\KnihovnyCz\Notifications::class,
            'searchMemory' => \KnihovnyCz\View\Helper\KnihovnyCz\SearchMemory::class,
            'socialLinks' => \KnihovnyCz\View\Helper\KnihovnyCz\SocialLinks::class,
            \VuFind\View\Helper\Root\GoogleTagManager::class => \KnihovnyCz\View\Helper\KnihovnyCz\GoogleTagManager::class,
            'palmknihy' => \KnihovnyCz\View\Helper\KnihovnyCz\Palmknihy::class,
        ],
    ],
    'icons' => [
        'aliases' => [
            /**
             * Icons can be assigned or overridden here
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
            'facet-collapse' => 'Unicode:25BD',
            'facet-expand' => 'Unicode:25B6',
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
            'notifications-management' => 'FontAwesome:envelope-o:fa-fw',
            'opening-hours' => 'FontAwesome:clock-o',
            'phone-number' => 'FontAwesome:phone',
            'regional-library-marker' => 'FontAwesome:dot-circle-o',
            'save-institution-filter' => 'FontAwesome:floppy-o:fa-fw',
            'send-email' => 'FontAwesome:envelope:fa-fw',
            'share-record' => 'FontAwesome:share-alt:fa-fw',
            'short-loans' => 'FontAwesome:calendar:fa-fw',
            'sign-in' => 'FontAwesome:sign-in:fa-fw',
            'sign-out' => 'FontAwesome:sign-out:fa-fw',
            'sign-up' => 'FontAwesome:user-plus:fa-fw',
            'spinner' => 'FontAwesome:spinner:fa-spin-pulse',
            'ui-menu' => 'FontAwesome:bars:fa-fw',
            'user-favorites' => 'FontAwesome:star',
            'user-list-add' => 'FontAwesome:star:fa-fw',
            'user-settings' => 'FontAwesome:gear:fa-fw',
            'user-ziskej' => 'FontAwesome:file-text:fa-fw',
            'user-ziskej-edd' => 'FontAwesome:file-text:fa-fw',
            'theme' => 'FontAwesome:sun-o:fa-fw',
            'wantit' => 'FontAwesome:hand-paper-o',
            'website' => 'FontAwesome:globe',
            'wheelchair-accessible' => 'FontAwesome:wheelchair-alt',
            'wheelchair-accessible-with-help' => 'FontAwesome:wheelchair',
            'wheelchair-inaccessible' => 'FontAwesome:ban',
            'wheelchair-partially-accessible' => 'FontAwesome:wheelchair',
            'wheelchair-partially-accessible-with-help' => 'FontAwesome:wheelchair',
            'wheelchair-accessible-for-visual-impairment' => 'FontAwesome:blind',
            'wheelchair-inaccessible-for-visual-impairment' => 'FontAwesome:ban',
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
    'favicon' => [
        [
            'href' => 'favicons/apple-touch-icon.png',
            'rel' => 'apple-touch-icon',
            'type' => 'image/png',
            'sizes' => '180x180',
        ],
        [
            'href' => 'favicons/favicon-32x32.png',
            'rel' => 'icon',
            'type' => 'image/png',
            'sizes' => '32x32',
        ],
        [
            'href' => 'favicons/favicon-16x16.png',
            'rel' => 'icon',
            'type' => 'image/png',
            'sizes' => '16x16',
        ],
        [
            'href' => 'favicons/safari-pinned-tab.svg',
            'rel' => 'mask-icon',
            'color' => '#5bbad5',
        ],
        [
            'href' => 'favicons/site.webmanifest',
            'rel' => 'manifest',
        ],
    ],
];
