<?php

namespace KnihovnyCz\Module\Configuration;

$config = [
     'router' => [
         'routes' => [
             'inspiration' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Inspiration',
                     'defaults' => [
                         'controller' => 'Inspiration',
                         'action' => 'Home',
                     ],
                 ],
             ],
             'inspiration-show-legacy' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/inspirace/[:list]',
                     'defaults' => [
                         'controller' => 'Inspiration',
                         'action' => 'Show',
                     ],
                 ],
             ],
             'inspiration-show' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Inspiration/[:list]',
                     'defaults' => [
                         'controller' => 'Inspiration',
                         'action' => 'Show',
                     ],
                 ],
             ],

             'inspiration-home-legacy' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Search/Inspiration',
                     'defaults' => [
                         'controller' => 'Inspiration',
                         'action' => 'HomeLegacy',
                     ],
                 ],
             ],
             'portal-pages' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Portal/Page/[:page]',
                     'constraints' => [
                         'page'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ],
                     'defaults' => [
                         'controller' => 'PortalPage',
                         'action' => 'Index',
                     ],
                 ],
             ],
             'wayf' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Wayf',
                     'defaults' => [
                         'controller' => 'Wayf',
                         'action' => 'Index',
                     ],
                 ],
             ],
             'search-legacy' => [
                 'type' => \Laminas\Router\Http\Literal::class,
                 'options' => [
                     'route' => '/Search/Results/',
                     'defaults' => [
                         'controller' => 'Search',
                         'action' => 'Results',
                     ],
                 ],
             ],
             'ziskej-admin' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/ziskej',
                     'defaults' => [
                         'controller' => 'ZiskejAdmin',
                         'action' => 'Home',
                     ],
                 ],
             ],
             'myresearch-ziskej-mvs-home' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/Ziskej',
                     'defaults' => [
                         'controller' => 'MyResearchZiskejMvs',
                         'action' => 'Home',
                     ],
                 ],
             ],
             'myresearch-ziskej-edd-home' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/ZiskejEdd',
                     'defaults' => [
                         'controller' => 'MyResearchZiskejEdd',
                         'action' => 'Home',
                     ],
                 ],
             ],
             'myresearch-ziskej-mvs-ticket' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/ZiskejTicket/[:eppnDomain]/[:ticketId]',
                     'constraints' => [
                         'eppnDomain'     => '.*',
                         'ticketId'     => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'MyResearchZiskejMvs',
                         'action' => 'Ticket',
                     ],
                 ],
             ],
             'myresearch-ziskej-edd-ticket' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/ZiskejEddTicket/[:eppnDomain]/[:ticketId]',
                     'constraints' => [
                         'eppnDomain'     => '.*',
                         'ticketId'     => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'MyResearchZiskejEdd',
                         'action' => 'Ticket',
                     ],
                 ],
             ],
             'myresearch-ziskej-mvs-ticket-cancel' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/ZiskejTicket/[:eppnDomain]/[:ticketId]/Cancel',
                     'constraints' => [
                         'eppnDomain'     => '.*',
                         'ticketId'     => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'MyResearchZiskejMvs',
                         'action' => 'TicketCancel',
                     ],
                 ],
             ],
             'myresearch-ziskej-edd-ticket-cancel' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/ZiskejEddTicket/[:eppnDomain]/[:ticketId]/Cancel',
                     'constraints' => [
                         'eppnDomain'     => '.*',
                         'ticketId'     => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'MyResearchZiskejEdd',
                         'action' => 'TicketCancel',
                     ],
                 ],
             ],
             'myresearch-ziskej-mvs-message-post' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/ZiskejTicket/[:eppnDomain]/[:ticketId]/Message',
                     'constraints' => [
                         'eppnDomain'     => '.*',
                         'ticketId'     => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'MyResearchZiskejMvs',
                         'action' => 'TicketMessage',
                     ],
                 ],
             ],
             'myresearch-ziskej-edd-message-post' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/ZiskejEddTicket/[:eppnDomain]/[:ticketId]/Message',
                     'constraints' => [
                         'eppnDomain'     => '.*',
                         'ticketId'     => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'MyResearchZiskejEdd',
                         'action' => 'TicketMessage',
                     ],
                 ],
             ],
             'ziskej-mvs-order' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Record/[:id]/ZiskejOrder/:eppnDomain',
                     'constraints' => [
                         'id' => '.*',
                         'eppnDomain' => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'Record',
                         'action' => 'ZiskejOrder',
                     ],
                 ],
             ],
             'ziskej-edd-order' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Record/[:id]/ZiskejEddOrder/:eppnDomain',
                     'constraints' => [
                         'id' => '.*',
                         'eppnDomain' => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'Record',
                         'action' => 'ZiskejEddOrder',
                     ],
                 ],
             ],
             'ziskej-mvs-order-post' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Record/[:id]/ZiskejOrderPost',
                     'constraints' => [
                         'id' => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'Record',
                         'action' => 'ZiskejOrderPost',
                     ],
                 ],
             ],
             'ziskej-edd-order-post' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Record/[:id]/ZiskejEddOrderPost',
                     'constraints' => [
                         'id' => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'Record',
                         'action' => 'ZiskejEddOrderPost',
                     ],
                 ],
             ],
             'ziskej-payment' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/ziskej/payment/:eppnDomain/:ticketId/:paymentTransactionId',
                     'constraints' => [
                         'eppnDomain' => '.*',
                         'ticketId' => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'Ziskej',
                         'action' => 'Payment',
                     ],
                 ],
             ],
             'ziskej-mvs-order-finished' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Ziskej/Finished/:eppnDomain/:ticketId',
                     'constraints' => [
                         'eppnDomain' => '.*',
                         'ticketId' => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'Ziskej',
                         'action' => 'Finished',
                     ],
                 ],
             ],
             'embedded-libraries' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Embedded/Libraries[/:region[/:district]]',
                     'constraints' => [
                         'region' => '[^\/]+',
                         'district' => '[^\/]+',
                     ],
                     'defaults' => [
                         'controller' => 'EmbeddedLibraries',
                         'action' => 'Index',
                     ],
                 ],
             ],
             'embedded-search' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Search/Embedded',
                     'defaults' => [
                         'controller' => 'EmbeddedSearch',
                         'action' => 'Index',
                     ],
                 ],
             ],
             'embedded-search-lowercased' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Search/embedded',
                     'defaults' => [
                         'controller' => 'EmbeddedSearch',
                         'action' => 'Index',
                     ],
                 ],
             ],
             'adminils' => [
                 'type' => 'Laminas\Router\Http\Segment',
                 'options' => [
                     'route'    => '/Admin/Ils/Status',
                     'defaults' => [
                         'controller' => 'AdminIls',
                         'action'     => 'Status',
                     ],
                 ],
             ],
             'notifications' => [
                 'type' => 'Laminas\Router\Http\Segment',
                 'options' => [
                     'route' => '/Notifications',
                     'defaults' => [
                         'controller' => 'Notification',
                         'action' => 'Home',
                     ],
                 ],
             ],
             'notifications-edit' => [
                 'type' => 'Laminas\Router\Http\Segment',
                 'options' => [
                     'route' => '/Notifications/Edit/[:id]',
                     'defaults' => [
                         'controller' => 'Notification',
                         'action' => 'Edit',
                     ],
                 ],
             ],
             'notifications-delete' => [
                 'type' => 'Laminas\Router\Http\Segment',
                 'options' => [
                     'route' => '/Notifications/Delete/[:id]',
                     'defaults' => [
                         'controller' => 'Notification',
                         'action' => 'Delete',
                     ],
                 ],
             ],
         ],
     ],
    'controllers' => [
        'factories' => [
            \KnihovnyCz\Controller\InspirationController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\PortalPageController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\WayfController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\LibraryCardsController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\MyResearchController::class => \VuFind\Controller\MyResearchControllerFactory::class,
            \KnihovnyCz\Controller\RecordController::class => \VuFind\Controller\AbstractBaseWithConfigFactory::class,
            \KnihovnyCz\Controller\SearchController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\MyResearchZiskejMvsController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\MyResearchZiskejEddController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\NotificationController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\ZiskejController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\ZiskejAdminController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\HoldsController::class => \VuFind\Controller\HoldsControllerFactory::class,
            \KnihovnyCz\Controller\ContentController::class => \KnihovnyCz\Controller\ContentControllerFactory::class,
            \KnihovnyCz\Controller\CartController::class => \VuFind\Controller\CartControllerFactory::class,
            \KnihovnyCz\Controller\EmbeddedController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\EmbeddedLibrariesController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\EmbeddedSearchController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\AdminIlsController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\CheckoutsController::class => \VuFind\Controller\CheckoutsControllerFactory::class,
            \KnihovnyCz\Controller\Search2recordController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\SummonrecordController::class => \VuFind\Controller\AbstractBaseFactory::class,

        ],
        'aliases' => [
            'Inspiration' => \KnihovnyCz\Controller\InspirationController::class,
            'PortalPage' => \KnihovnyCz\Controller\PortalPageController::class,
            'Wayf' => \KnihovnyCz\Controller\WayfController::class,
            'LibraryCards' => \KnihovnyCz\Controller\LibraryCardsController::class,
            'ZiskejAdmin' => \KnihovnyCz\Controller\ZiskejAdminController::class,
            'Ziskej' => \KnihovnyCz\Controller\ZiskejController::class,
            'MyResearchZiskejMvs' => \KnihovnyCz\Controller\MyResearchZiskejMvsController::class,
            'MyResearchZiskejEdd' => \KnihovnyCz\Controller\MyResearchZiskejEddController::class,
            'MyResearch' => \KnihovnyCz\Controller\MyResearchController::class,
            'Notification' => \KnihovnyCz\Controller\NotificationController::class,
            \VuFind\Controller\RecordController::class => \KnihovnyCz\Controller\RecordController::class,
            \VuFind\Controller\SearchController::class => \KnihovnyCz\Controller\SearchController::class,
            \VuFind\Controller\HoldsController::class => \KnihovnyCz\Controller\HoldsController::class,
            \VuFind\Controller\ContentController::class => \KnihovnyCz\Controller\ContentController::class,
            \VuFind\Controller\CartController::class => \KnihovnyCz\Controller\CartController::class,
            'Embedded' => \KnihovnyCz\Controller\EmbeddedController::class,
            'EmbeddedLibraries' => \KnihovnyCz\Controller\EmbeddedLibrariesController::class,
            'EmbeddedSearch' => \KnihovnyCz\Controller\EmbeddedSearchController::class,
            'AdminIls' => \KnihovnyCz\Controller\AdminIlsController::class,
            \VuFind\Controller\CheckoutsController::class => \KnihovnyCz\Controller\CheckoutsController::class,
            \VuFind\Controller\Search2recordController::class => \KnihovnyCz\Controller\Search2recordController::class,
            \VuFind\Controller\SummonrecordController::class => \KnihovnyCz\Controller\SummonrecordController::class,
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            \KnihovnyCz\Controller\Plugin\ShortLoans::class => \KnihovnyCz\Controller\Plugin\ShortLoans::class,
        ],
        'factories' => [
            'KnihovnyCz\Controller\Plugin\Holds' => \KnihovnyCz\Controller\Plugin\HoldsFactory::class,
            'KnihovnyCz\Controller\Plugin\FlashRedirect' => 'VuFind\Controller\Plugin\AbstractRequestBaseFactory',
            'KnihovnyCz\Controller\Plugin\ResultScroller' => \KnihovnyCz\Controller\Plugin\ResultScrollerFactory::class,
        ],
        'aliases' => [
            'holds' => 'KnihovnyCz\Controller\Plugin\Holds',
            'flashRedirect' => 'KnihovnyCz\Controller\Plugin\FlashRedirect',
            'resultScroller' => 'KnihovnyCz\Controller\Plugin\ResultScroller',
            'shortLoans' => \KnihovnyCz\Controller\Plugin\ShortLoans::class,
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'auth' => [
                'factories' => [
                    \KnihovnyCz\Auth\Shibboleth::class => \VuFind\Auth\ShibbolethFactory::class,
                ],
                'aliases' => [
                    \VuFind\Auth\Shibboleth::class => \KnihovnyCz\Auth\Shibboleth::class,
                ],
            ],
            'recorddriver' =>  [
                'factories' => [
                    \KnihovnyCz\RecordDriver\SolrAuthority::class => \KnihovnyCz\RecordDriver\SolrDefaultFactory::class,
                    \KnihovnyCz\RecordDriver\SolrDefault::class => \KnihovnyCz\RecordDriver\SolrDefaultFactory::class,
                    \KnihovnyCz\RecordDriver\SolrDictionary::class => \KnihovnyCz\RecordDriver\SolrDefaultFactory::class,
                    \KnihovnyCz\RecordDriver\SolrDublinCore::class => \KnihovnyCz\RecordDriver\SolrDefaultFactory::class,
                    \KnihovnyCz\RecordDriver\SolrLibrary::class => \KnihovnyCz\RecordDriver\SolrLibraryFactory::class,
                    \KnihovnyCz\RecordDriver\SolrMarc::class => \KnihovnyCz\RecordDriver\SolrDefaultFactory::class,
                    \KnihovnyCz\RecordDriver\SolrLocal::class => \KnihovnyCz\RecordDriver\SolrLocalFactory::class,
                    \KnihovnyCz\RecordDriver\Search2Library::class => \KnihovnyCz\RecordDriver\SolrLibraryFactory::class,
                    \KnihovnyCz\RecordDriver\EDS::class => \KnihovnyCz\RecordDriver\EdsFactory::class,
                    \KnihovnyCz\RecordDriver\Summon::class => \KnihovnyCz\RecordDriver\SummonFactory::class,
                    \KnihovnyCz\RecordDriver\Missing::class => \VuFind\RecordDriver\AbstractBaseFactory::class,
                ],
                'aliases' => [
                    'solrauthority' => \KnihovnyCz\RecordDriver\SolrAuthority::class,
                    'solrdefault' => \KnihovnyCz\RecordDriver\SolrDefault::class,
                    'solrdictionary' => \KnihovnyCz\RecordDriver\SolrDictionary::class,
                    'solrdublincore' => \KnihovnyCz\RecordDriver\SolrDublinCore::class,
                    'solrlibrary' => \KnihovnyCz\RecordDriver\SolrLibrary::class,
                    \VuFind\RecordDriver\SolrMarc::class => \KnihovnyCz\RecordDriver\SolrMarc::class,
                    'solrlocal' => \KnihovnyCz\RecordDriver\SolrLocal::class,
                    'search2library' => \KnihovnyCz\RecordDriver\Search2Library::class,
                    'eds' => \KnihovnyCz\RecordDriver\EDS::class,
                    'summon' => \KnihovnyCz\RecordDriver\Summon::class,
                    'missing' => \KnihovnyCz\RecordDriver\Missing::class,
                ],
                'delegators' => [
                    \KnihovnyCz\RecordDriver\SolrMarc::class => [
                        \VuFind\RecordDriver\IlsAwareDelegatorFactory::class,
                    ],
                    \KnihovnyCz\RecordDriver\SolrLocal::class => [
                        \VuFind\RecordDriver\IlsAwareDelegatorFactory::class,
                    ],
                ],
            ],
            'recordtab' => [
                'invokables' => [
                    'buy' => \KnihovnyCz\RecordTab\Buy::class,
                    'cites' => \KnihovnyCz\RecordTab\Cites::class,
                    'dedupedrecords' => \KnihovnyCz\RecordTab\DedupedRecords::class,
                    'sfxavailability' => \KnihovnyCz\RecordTab\SfxAvailability::class,
                    'eversion' => \KnihovnyCz\RecordTab\EVersion::class,
                    'librarybranches' => \KnihovnyCz\RecordTab\LibraryBranches::class,
                    'librarycontacts' => \KnihovnyCz\RecordTab\LibraryContacts::class,
                    'libraryinfo' => \KnihovnyCz\RecordTab\LibraryInfo::class,
                    'libraryservices' => \KnihovnyCz\RecordTab\LibraryServices::class,
                    'staffviewdublincore' => \KnihovnyCz\RecordTab\StaffViewDublinCore::class,
                ],
                'factories' => [
                    \KnihovnyCz\RecordTab\HoldingsILS::class => \VuFind\RecordTab\HoldingsILSFactory::class,
                    \KnihovnyCz\RecordTab\ZiskejMvs::class => \KnihovnyCz\RecordTab\ZiskejMvsFactory::class,
                    \KnihovnyCz\RecordTab\ZiskejEdd::class => \KnihovnyCz\RecordTab\ZiskejEddFactory::class,
                ],
                'aliases' => [
                    \VuFind\RecordTab\HoldingsILS::class => \KnihovnyCz\RecordTab\HoldingsILS::class,
                    'ziskejMvs' => \KnihovnyCz\RecordTab\ZiskejMvs::class,
                    'ziskejEdd' => \KnihovnyCz\RecordTab\ZiskejEdd::class,
                ],
            ],
            'contentblock' => [
                'factories' => [
                    \KnihovnyCz\ContentBlock\DocumentTypes::class => \KnihovnyCz\ContentBlock\DocumentTypesFactory::class,
                    \KnihovnyCz\ContentBlock\UserList::class => \KnihovnyCz\ContentBlock\AbstractDbAwaredRecordIdsFactory::class,
                    \KnihovnyCz\ContentBlock\TemplateBased::class => \KnihovnyCz\ContentBlock\TemplateBasedFactory::class,
                    \KnihovnyCz\ContentBlock\TemplateBasedForLoggedIn::class => \KnihovnyCz\ContentBlock\TemplateBasedForLoggedInFactory::class,
                    \KnihovnyCz\ContentBlock\TemplateBasedForUnloggedIn::class => \KnihovnyCz\ContentBlock\TemplateBasedForUnloggedInFactory::class,
                ],
                'aliases' => [
                    'documenttypes' => \KnihovnyCz\ContentBlock\DocumentTypes::class,
                    'userlist' => \KnihovnyCz\ContentBlock\UserList::class,
                    \VuFind\ContentBlock\TemplateBased::class => \KnihovnyCz\ContentBlock\TemplateBased::class,
                    'templatebasedforloggedin' => \KnihovnyCz\ContentBlock\TemplateBasedForLoggedIn::class,
                    'templatebasedforunloggedin' => \KnihovnyCz\ContentBlock\TemplateBasedForUnloggedIn::class,
                ],
            ],
            'db_row' => [
                'factories' => [
                    \KnihovnyCz\Db\Row\Config::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\InstConfigs::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\InstSources::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\User::class => \KnihovnyCz\Db\Row\UserFactory::class,
                    \KnihovnyCz\Db\Row\UserCard::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\CsrfToken::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\UserList::class => \KnihovnyCz\Db\Row\UserListFactory::class,
                    \KnihovnyCz\Db\Row\UserListCategories::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\UserSettings::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\Resource::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\RecordStatus::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\Notifications::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\Search::class => \VuFind\Db\Row\RowGatewayFactory::class,
                ],
                'aliases' => [
                    \VuFind\Db\Row\User::class => \KnihovnyCz\Db\Row\User::class,
                    \VuFind\Db\Row\UserCard::class => \KnihovnyCz\Db\Row\UserCard::class,
                    \VuFind\Db\Row\UserList::class => \KnihovnyCz\Db\Row\UserList::class,
                    \VuFind\Db\Row\Resource::class => \KnihovnyCz\Db\Row\Resource::class,
                    \VuFind\Db\Row\Search::class => \KnihovnyCz\Db\Row\Search::class,
                ],
            ],
            'db_service' => [
                'factories' => [
                    \KnihovnyCz\Db\Service\NotificationsService::class => \VuFind\Db\Service\AbstractDbServiceFactory::class,
                    \KnihovnyCz\Db\Service\UserService::class => \VuFind\Db\Service\UserServiceFactory::class,
                    \KnihovnyCz\Db\Service\CsrfTokenService::class => \VuFind\Db\Service\AbstractDbServiceFactory::class,
                ],
                'aliases' => [
                    \KnihovnyCz\Db\Service\NotificationsServiceInterface::class => \KnihovnyCz\Db\Service\NotificationsService::class,
                    \VuFind\Db\Service\UserService::class => \KnihovnyCz\Db\Service\UserService::class,

                ],
            ],
            'db_table' => [
                'factories' => [
                    \KnihovnyCz\Db\Table\Config::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\InstConfigs::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\InstSources::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\User::class => \VuFind\Db\Table\UserFactory::class,
                    \KnihovnyCz\Db\Table\UserCard::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\CsrfToken::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\UserSettings::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\Resource::class => \VuFind\Db\Table\ResourceFactory::class,
                    \KnihovnyCz\Db\Table\UserList::class => \VuFind\Db\Table\UserListFactory::class,
                    \KnihovnyCz\Db\Table\UserListCategories::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\RecordStatus::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\Notifications::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\Search::class => \VuFind\Db\Table\GatewayFactory::class,
                ],
                'aliases' => [
                    \VuFind\Db\Table\User::class => \KnihovnyCz\Db\Table\User::class,
                    \VuFind\Db\Table\UserCard::class => \KnihovnyCz\Db\Table\UserCard::class,
                    \VuFind\Db\Table\UserList::class => \KnihovnyCz\Db\Table\UserList::class,
                    'UserSettings' => \KnihovnyCz\Db\Table\UserSettings::class,
                    \VuFind\Db\Table\Resource::class => \KnihovnyCz\Db\Table\Resource::class,
                    'notifications' => \KnihovnyCz\Db\Table\Notifications::class,
                    'CsrfToken' => \KnihovnyCz\Db\Table\CsrfToken::class,
                ],
            ],
            'ils_driver' => [
                'factories' => [
                    \KnihovnyCz\ILS\Driver\KohaRest::class => \VuFind\ILS\Driver\KohaRestFactory::class,
                    \KnihovnyCz\ILS\Driver\KohaRest1905::class => \VuFind\ILS\Driver\DriverWithDateConverterFactory::class,
                    \KnihovnyCz\ILS\Driver\MultiBackend::class => \KnihovnyCz\ILS\Driver\MultiBackendFactory::class,
                    \KnihovnyCz\ILS\Driver\XCNCIP2::class => \VuFind\ILS\Driver\DriverWithDateConverterFactory::class,
                    \KnihovnyCz\ILS\Driver\Aleph::class => \VuFind\ILS\Driver\AlephFactory::class,
                    \KnihovnyCz\ILS\Driver\AlephMzk::class => \VuFind\ILS\Driver\AlephFactory::class,
                    \KnihovnyCz\ILS\Driver\AlephNkp::class => \VuFind\ILS\Driver\AlephFactory::class,
                    \KnihovnyCz\ILS\Driver\NoILS::class => \VuFind\ILS\Driver\NoILSFactory::class,
                ],
                'aliases' => [
                    'koharest' => \KnihovnyCz\ILS\Driver\KohaRest::class,
                    'koharest1905' => \KnihovnyCz\ILS\Driver\KohaRest1905::class,
                    'multibackend' => \KnihovnyCz\ILS\Driver\MultiBackend::class,
                    'xcncip2' => \KnihovnyCz\ILS\Driver\XCNCIP2::class,
                    'aleph' => \KnihovnyCz\ILS\Driver\Aleph::class,
                    'alephmzk' => \KnihovnyCz\ILS\Driver\AlephMzk::class,
                    'alephnkp' => \KnihovnyCz\ILS\Driver\AlephNkp::class,
                    'noils' => \KnihovnyCz\ILS\Driver\NoILS::class,
                ],
            ],
            'content_toc' => [
                'factories' => [
                    \KnihovnyCz\Content\TOC\ObalkyKnih::class => \VuFind\Content\ObalkyKnihContentFactory::class,
                ],
                'aliases' => [
                    'obalkyknih' => \KnihovnyCz\Content\TOC\ObalkyKnih::class,
                ],
            ],
            'ajaxhandler' => [
                'factories' => [
                    \KnihovnyCz\AjaxHandler\Edd::class => \KnihovnyCz\AjaxHandler\EddFactory::class,
                    \KnihovnyCz\AjaxHandler\GetCitations::class => \KnihovnyCz\AjaxHandler\GetCitationsFactory::class,
                    \KnihovnyCz\AjaxHandler\GetHolding::class => \KnihovnyCz\AjaxHandler\GetHoldingFactory::class,
                    \KnihovnyCz\AjaxHandler\GetObalkyKnihCoverWithoutSolr::class => \KnihovnyCz\AjaxHandler\GetObalkyKnihCoverWithoutSolrFactory::class,
                    \KnihovnyCz\AjaxHandler\GetACSuggestions::class => \KnihovnyCz\AjaxHandler\GetACSuggestionsFactory::class,
                    \KnihovnyCz\AjaxHandler\GetItemStatuses::class => \KnihovnyCz\AjaxHandler\GetItemStatusesFactory::class,
                    \KnihovnyCz\AjaxHandler\GetLibrariesACSuggestions::class => \KnihovnyCz\AjaxHandler\GetLibrariesACSuggestionsFactory::class,
                    \KnihovnyCz\AjaxHandler\HarvestWidgetsContents::class => \KnihovnyCz\AjaxHandler\HarvestWidgetsContentsFactory::class,
                    \KnihovnyCz\AjaxHandler\Sfx::class => \KnihovnyCz\AjaxHandler\SfxFactory::class,
                    \KnihovnyCz\AjaxHandler\SaveInstitutionFilter::class => \KnihovnyCz\AjaxHandler\SaveInstitutionFilterFactory::class,
                    \VuFind\AjaxHandler\GetUserTransactions::class => \KnihovnyCz\AjaxHandler\AbstractIlsAndUserActionFactory::class,
                    \VuFind\AjaxHandler\GetUserHolds::class => \KnihovnyCz\AjaxHandler\AbstractIlsAndUserActionFactory::class,
                    \VuFind\AjaxHandler\GetUserFines::class => \KnihovnyCz\AjaxHandler\GetUserFinesFactory::class,
                    \VuFind\AjaxHandler\GetUserILLRequests::class => \KnihovnyCz\AjaxHandler\AbstractIlsAndUserActionFactory::class,
                    \KnihovnyCz\AjaxHandler\GetUserProfile::class => \KnihovnyCz\AjaxHandler\AbstractIlsAndUserActionFactory::class,
                    \KnihovnyCz\AjaxHandler\GetZiskejEddFee::class => \KnihovnyCz\AjaxHandler\GetZiskejEddFeeFactory::class,
                    \KnihovnyCz\AjaxHandler\GetIlsDriverStatus::class => \KnihovnyCz\AjaxHandler\GetIlsDriverStatusFactory::class,
                ],
                'aliases' => [
                    'edd' => \KnihovnyCz\AjaxHandler\Edd::class,
                    'getcitations' => \KnihovnyCz\AjaxHandler\GetCitations::class,
                    'getHolding' => \KnihovnyCz\AjaxHandler\GetHolding::class,
                    'getObalkyKnihCoverWithoutSolr' => \KnihovnyCz\AjaxHandler\GetObalkyKnihCoverWithoutSolr::class,
                    'getACSuggestions' => \KnihovnyCz\AjaxHandler\GetACSuggestions::class,
                    'getItemStatuses' => \KnihovnyCz\AjaxHandler\GetItemStatuses::class,
                    'getLibrariesACSuggestions' => \KnihovnyCz\AjaxHandler\GetLibrariesACSuggestions::class,
                    'harvestWidgetsContents' => \KnihovnyCz\AjaxHandler\HarvestWidgetsContents::class,
                    'sfx' => \KnihovnyCz\AjaxHandler\Sfx::class,
                    'saveInstitutionFilter' => \KnihovnyCz\AjaxHandler\SaveInstitutionFilter::class,
                    'getUserProfile' => \KnihovnyCz\AjaxHandler\GetUserProfile::class,
                    'getZiskejEddFee' => \KnihovnyCz\AjaxHandler\GetZiskejEddFee::class,
                    'getIlsDriverStatus' => \KnihovnyCz\AjaxHandler\GetIlsDriverStatus::class,
                ],
            ],
            'related' => [
                'invokables' => [
                    \KnihovnyCz\Related\Authorities::class,
                    \KnihovnyCz\Related\SolrField::class,
                    \KnihovnyCz\Related\Links::class,
                ],
                'aliases' => [
                    'authorities' => \KnihovnyCz\Related\Authorities::class,
                    'solrfield' => \KnihovnyCz\Related\SolrField::class,
                    'links' => \KnihovnyCz\Related\Links::class,
                ],
            ],
            'search_backend' => [
                'factories' => [
                    'Solr' => \KnihovnyCz\Search\Factory\SolrDefaultBackendFactory::class,
                    'SolrAutocomplete' => \KnihovnyCz\Search\Factory\SolrAutocompleteBackendFactory::class,
                    'Search2' => \KnihovnyCz\Search\Factory\Search2BackendFactory::class,
                    'EDS' => \KnihovnyCz\Search\Factory\EdsBackendFactory::class,
                ],
            ],
            'search_options' => [
                'factories' => [
                    \KnihovnyCz\Search\Favorites\Options::class => \VuFind\Search\Options\OptionsFactory::class,
                    \KnihovnyCz\Search\SolrAutocomplete\Options::class => \VuFind\Search\Options\OptionsFactory::class,
                ],
                'aliases' => [
                    \VuFind\Search\Favorites\Options::class => \KnihovnyCz\Search\Favorites\Options::class,
                ],
            ],
            'search_params' => [
                'factories' => [
                    \KnihovnyCz\Search\Solr\Params::class => \KnihovnyCz\Search\Solr\ParamsFactory::class,
                    \KnihovnyCz\Search\Search2\Params::class => \KnihovnyCz\Search\Solr\ParamsFactory::class,
                    \KnihovnyCz\Search\SolrAutocomplete\Params::class => \KnihovnyCz\Search\Solr\ParamsFactory::class,
                ],
                'aliases' => [
                    \VuFind\Search\Solr\Params::class => \KnihovnyCz\Search\Solr\Params::class,
                    \VuFind\Search\Search2\Params::class => \KnihovnyCz\Search\Search2\Params::class,
                ],
            ],
            'search_results' => [
                'factories' => [
                    \KnihovnyCz\Search\Solr\Results::class => \VuFind\Search\Solr\ResultsFactory::class,
                    \KnihovnyCz\Search\SolrAutocomplete\Results::class => \VuFind\Search\Solr\ResultsFactory::class,
                ],
                'aliases' => [
                   'SolrAutocomplete' => \KnihovnyCz\Search\SolrAutocomplete\Results::class,
                    \VuFind\Search\Solr\Results::class => \KnihovnyCz\Search\Solr\Results::class,
                ],
            ],
            'autocomplete' => [
                'factories' => [
                    \KnihovnyCz\Autocomplete\SolrPrefix::class => \KnihovnyCz\Autocomplete\SolrFactoryWithFilter::class,
                    \KnihovnyCz\Autocomplete\SolrPrefixSorted::class => \KnihovnyCz\Autocomplete\SolrFactoryWithFilter::class,
                    \KnihovnyCz\Autocomplete\SolrPrefixHandler::class => \KnihovnyCz\Autocomplete\SolrFactoryWithFilter::class,
                    \KnihovnyCz\Autocomplete\SolrAuthorityEnricher::class => \KnihovnyCz\Autocomplete\SolrAuthorityEnricherFactory::class,
                ],
                'aliases' => [
                    'solrprefix' => \KnihovnyCz\Autocomplete\SolrPrefix::class,
                    'solrprefixsorted' => \KnihovnyCz\Autocomplete\SolrPrefixSorted::class,
                    'solrprefixhandler' => \KnihovnyCz\Autocomplete\SolrPrefixHandler::class,
                    'solrauthorityenricher' => \KnihovnyCz\Autocomplete\SolrAuthorityEnricher::class,
                ],
            ],
            'recommend' => [
                'factories' => [
                    \KnihovnyCz\Recommend\RecommendOtherPortals::class => \VuFind\Recommend\InjectConfigManagerFactory::class,
                    \KnihovnyCz\Recommend\MapSelection::class => \KnihovnyCz\Recommend\MapSelectionFactory::class,
                    \KnihovnyCz\Recommend\NewItems::class => \KnihovnyCz\Recommend\NewItemsFactory::class,
                    \KnihovnyCz\Recommend\SideFacetsDeferred::class => \KnihovnyCz\Recommend\SideFacetsDeferredFactory::class,
                ],
                'aliases' => [
                    'recommendotherportals' => \KnihovnyCz\Recommend\RecommendOtherPortals::class,
                    'mapselection' => \KnihovnyCz\Recommend\MapSelection::class,
                    'newitems' => \KnihovnyCz\Recommend\NewItems::class,
                    'sidefacetsdeferred' => \KnihovnyCz\Recommend\SideFacetsDeferred::class,
                ],
            ],
            'sitemap' => [
                'factories' => [
                    \KnihovnyCz\Sitemap\Plugin\Index::class => \KnihovnyCz\Sitemap\Plugin\IndexFactory::class,
                ],
                'aliases' => [
                    \VuFind\Sitemap\Plugin\Index::class => \KnihovnyCz\Sitemap\Plugin\Index::class,
                ],
            ],
            'form_handler' => [
                'factories' => [
                    \KnihovnyCz\Form\Handler\AskLibrary::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                    \KnihovnyCz\Form\Handler\DigitalizationRequest::class => \KnihovnyCz\Form\Handler\DigitalizationRequestFactory::class,
                ],
                'aliases' => [
                    'asklibrary' => \KnihovnyCz\Form\Handler\AskLibrary::class,
                    'digitalizationrequest' => \KnihovnyCz\Form\Handler\DigitalizationRequest::class,
                ],
            ],
            'record_fallbackloader' => [
                'factories' => [
                    \KnihovnyCz\Record\FallbackLoader\Summon::class => \VuFind\Record\FallbackLoader\AbstractFallbackLoaderFactory::class,
                ],
                'aliases' => [
                    'summon' => \KnihovnyCz\Record\FallbackLoader\Summon::class,
                ],
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            \KnihovnyCz\Service\CitaceProService::class => \KnihovnyCz\Service\CitaceProServiceFactory::class,
            \KnihovnyCz\Config\PluginManager::class => \KnihovnyCz\Config\PluginManagerFactory::class,
            \KnihovnyCz\Config\AccountCapabilities::class => \VuFind\Config\AccountCapabilitiesFactory::class,
            \KnihovnyCz\Content\ObalkyKnihService::class => \VuFind\Content\ObalkyKnihServiceFactory::class,
            \KnihovnyCz\Content\PageLocator::class => \KnihovnyCz\Content\PageLocatorFactory::class,
            \KnihovnyCz\Form\IllForm::class => \KnihovnyCz\Form\IllFormFactory::class,
            \KnihovnyCz\ILS\Service\SolrIdResolver::class => \KnihovnyCz\ILS\Service\SolrIdResolverFactory::class,
            \KnihovnyCz\Service\WayfFilterGenerator::class => \KnihovnyCz\Service\WayfFilterGeneratorFactory::class,
            \Mzk\ZiskejApi\Api::class => \KnihovnyCz\Ziskej\ZiskejApiFactory::class,
            \KnihovnyCz\Ziskej\ZiskejEdd::class => \KnihovnyCz\Ziskej\ZiskejFactory::class,
            \KnihovnyCz\Ziskej\ZiskejMvs::class => \KnihovnyCz\Ziskej\ZiskejFactory::class,
            \KnihovnyCz\Auth\Manager::class => \KnihovnyCz\Auth\ManagerFactory::class,
            \KnihovnyCz\Autocomplete\Suggester::class => \VuFind\Autocomplete\SuggesterFactory::class,
            \KnihovnyCz\Service\HttpService::class => \KnihovnyCz\Service\HttpServiceFactory::class,
            \KnihovnyCz\Service\GuzzleHttpService::class => \KnihovnyCz\Service\GuzzleHttpServiceFactory::class,
            \KnihovnyCz\Http\PerformanceLogger::class => \KnihovnyCz\Http\PerformanceLoggerFactory::class,
            \KnihovnyCz\Validator\DatabaseCsrf::class => \KnihovnyCz\Validator\DatabaseCsrfFactory::class,
            \KnihovnyCz\ILS\Connection::class => \VuFind\ILS\ConnectionFactory::class,
            \KnihovnyCz\ILS\MultiConnection::class => \KnihovnyCz\ILS\MultiConnectionFactory::class,
            \KnihovnyCz\Date\Converter::class => \VuFind\Service\DateConverterFactory::class,
            \KnihovnyCz\Record\Loader::class => \KnihovnyCz\Record\LoaderFactory::class,
            \KnihovnyCz\Markdown\InvolvedLibraries\InvolvedLibrariesExtension::class => \KnihovnyCz\Markdown\InvolvedLibraries\InvolvedLibrariesExtensionFactory::class,
            \KnihovnyCz\Markdown\InvolvedLibrariesCount\InvolvedLibrariesCountExtension::class => \KnihovnyCz\Markdown\InvolvedLibrariesCount\InvolvedLibrariesCountExtensionFactory::class,
            \KnihovnyCz\Content\InvolvedLibrariesService::class => \KnihovnyCz\Content\InvolvedLibrariesServiceFactory::class,
            \KnihovnyCz\Db\Service\UserSettingsService::class => \KnihovnyCz\Db\Service\UserSettingsServiceFactory::class,
            \KnihovnyCz\ILS\Logic\Holds::class => \VuFind\ILS\Logic\LogicFactory::class,
            \KnihovnyCz\Wikidata\SparqlService::class => \KnihovnyCz\Wikidata\SparqlServiceFactory::class,
            \KnihovnyCz\Autocomplete\SuggestionFilter::class => \KnihovnyCz\Autocomplete\SuggestionFilterFactory::class,
            \KnihovnyCz\Cache\Manager::class => \VuFind\Cache\ManagerFactory::class,
            \KnihovnyCz\Search\Factory\UrlQueryHelperFactory::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            \KnihovnyCz\Markdown\EmbeddedUrlGenerator\EmbeddedUrlGeneratorExtension::class => \KnihovnyCz\Markdown\EmbeddedUrlGenerator\EmbeddedUrlGeneratorExtensionFactory::class,
            \KnihovnyCz\Search\History::class  => \VuFind\Search\HistoryFactory::class,
        ],
        'aliases' => [
            'VuFind\Http' => 'VuFindHttp\HttpService',
            'VuFindHttp\HttpService' => \KnihovnyCz\Service\HttpService::class,
            \VuFind\Config\PluginManager::class => \KnihovnyCz\Config\PluginManager::class,
            \VuFind\Config\AccountCapabilities::class => \KnihovnyCz\Config\AccountCapabilities::class,
            \VuFind\Content\ObalkyKnihService::class => \KnihovnyCz\Content\ObalkyKnihService::class,
            \VuFind\Content\PageLocator::class => \KnihovnyCz\Content\PageLocator::class,
            \VuFind\Auth\Manager::class => \KnihovnyCz\Auth\Manager::class,
            \VuFind\Autocomplete\Suggester::class => \KnihovnyCz\Autocomplete\Suggester::class,
            'Laminas\Validator\Csrf' => \KnihovnyCz\Validator\DatabaseCsrf::class,
            'VuFind\Validator\Csrf' => \KnihovnyCz\Validator\DatabaseCsrf::class,
            \VuFind\Validator\CsrfInterface::class => \KnihovnyCz\Validator\DatabaseCsrf::class,
            \VuFind\ILS\Connection::class => \KnihovnyCz\ILS\Connection::class,
            \VuFind\Date\Converter::class => \KnihovnyCz\Date\Converter::class,
            \VuFind\Record\Loader::class => \KnihovnyCz\Record\Loader::class,
            \VuFind\Search\Solr\Results::class => \KnihovnyCz\Search\Solr\Results::class,
            \VuFind\ILS\Logic\Holds::class => \KnihovnyCz\ILS\Logic\Holds::class,
            \VuFind\Cache\Manager::class => \KnihovnyCz\Cache\Manager::class,
            \VuFind\Search\Factory\UrlQueryHelperFactory::class => \KnihovnyCz\Search\Factory\UrlQueryHelperFactory::class,
            \VuFind\Search\History::class => \KnihovnyCz\Search\History::class,
        ],
        'invokables' => [
            \KnihovnyCz\Service\GoogleBooksLinkService::class,
            \KnihovnyCz\Service\ZboziLinkService::class,
            \KnihovnyCz\ILS\Logic\Holdings::class,
            \KnihovnyCz\Geo\Parser::class,
            \KnihovnyCz\HeadersListener::class => \KnihovnyCz\HeadersListener::class,
        ],
    ],
    'listeners' => [
        \KnihovnyCz\HeadersListener::class,
    ],
    // Config http:
    'http' => [
        // Config http headers:
        'headers' => [
            // all routes:
            '*' => [
                'Permissions-Policy' => '',
                'Referrer-Policy' => 'same-origin',
                'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'deny',
                'X-Permitted-Cross-Domain-Policies' => 'none',
                'X-XSS-Protection' => '1; mode=block',
            ],
            // specific routes:
            'embedded-libraries' => [
                'X-Frame-Options' => 'allow',
            ],
            'embedded-search' => [
                'X-Frame-Options' => 'allow',
            ],
        ],
    ],
];

// Define record view routes -- route name => [controller, route]
$recordRoutes = [
    'search2record' => ['Search2Record', 'LibraryRecord'],
    'search2collection' => ['Search2Collection', 'LibraryCollection'],
    'search2collectionrecord' => ['Search2Record', 'LibraryRecord'],
];

// Key is URL, value is Controller/Action
$staticRoutes = [
    'Inspiration/Conspectus' => 'Inspiration/Conspectus',
    'Libraries/Advanced' => 'Search2/Advanced',
    'Libraries/FacetList' => 'Search2/FacetList',
    'Libraries/Home' => 'Search2/Home',
    'Libraries/Results' => 'Search2/Results',
    'Libraries/Versions' => 'Search2/Versions',
    'MyResearch/DeleteUser' => 'MyResearch/DeleteUser',
    'MyResearch/FinesAjax' => 'MyResearch/FinesAjax',
    'MyResearch/ProfileAjax' => 'MyResearch/ProfileAjax',
    'MyResearch/CheckedoutAjax' => 'MyResearch/CheckedoutAjax',
    'Checkouts/HistoryAjax' => 'Checkouts/HistoryAjax',
    'MyResearch/UserSettings' => 'MyResearch/UserSettings',
    'MyResearch/ShortLoans' => 'MyResearch/ShortLoans',
    'MyResearch/ShortLoansAjax' => 'MyResearch/ShortLoansAjax',
    'MyResearch/IllRequestsAjax' => 'MyResearch/IllRequestsAjax',
    'MyResearch/IllRequestNew' => 'MyResearch/IllRequestNew',
    'MyResearch/Directlogin' => 'MyResearch/Directlogin',
    'Holds/ListAjax' => 'Holds/ListAjax',
    'MyResearchZiskejMvs/ListAjax' => 'MyResearchZiskejMvs/ListAjax',
    'MyResearchZiskejEdd/ListAjax' => 'MyResearchZiskejEdd/ListAjax',
    'MyResearch/LogoutWarning' => 'MyResearch/LogoutWarning',
    'MyResearch/FrontPageLogin' => 'MyResearch/FrontPageLogin',
    'MyResearch/ProfileChange' => 'MyResearch/ProfileChange',
    'MyResearch/ProfileChangeEmail' => 'MyResearch/ProfileChangeEmail',
    'MyResearch/ProfileChangeNickname' => 'MyResearch/ProfileChangeNickname',
    'MyResearch/ProfileChangePassword' => 'MyResearch/ProfileChangePassword',
    'LibraryCards/NotSupported' => 'LibraryCards/NotSupported',
    'Cart/Cite' => 'Cart/Cite',
];

$nonTabRecordActions = [
    'shortLoan', 'Share',
];

$routeGenerator = new \KnihovnyCz\Route\RouteGenerator();
$routeGenerator->addNonTabRecordActions($config, $nonTabRecordActions);
$routeGenerator->addRecordRoutes($config, $recordRoutes);
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;
