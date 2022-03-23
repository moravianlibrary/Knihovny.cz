<?php
/**
 * Knihovny.cz Module Configuration
 *
 * PHP version 7
 *
 * Copyright (C) The Moravian Library 2015-2019.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Knihovny.cz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
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
                         'action' => 'Home'
                     ],
                 ],
             ],
             'inspiration-show-legacy' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/inspirace/[:list]',
                     'defaults' => [
                         'controller' => 'Inspiration',
                         'action' => 'Show'
                     ],
                 ],
             ],
             'inspiration-show' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Inspiration/[:list]',
                     'defaults' => [
                         'controller' => 'Inspiration',
                         'action' => 'Show'
                     ],
                 ],
             ],

             'inspiration-home-legacy' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Search/Inspiration',
                     'defaults' => [
                         'controller' => 'Inspiration',
                         'action' => 'HomeLegacy'
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
                         'action' => 'Results'
                     ],
                 ],
             ],
             'ziskej-admin' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/ziskej',
                     'defaults' => [
                         'controller' => 'ZiskejAdmin',
                         'action' => 'Home'
                     ],
                 ],
             ],
             'myresearch-ziskej-home' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/Ziskej',
                     'defaults' => [
                         'controller' => 'MyResearchZiskej',
                         'action' => 'Home'
                     ],
                 ],
             ],
             'myresearch-ziskej-ticket' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/ZiskejTicket/[:eppnDomain]/[:ticketId]',
                     'constraints' => [
                         'eppnDomain'     => '.*',
                         'ticketId'     => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'MyResearchZiskej',
                         'action' => 'Ticket'
                     ],
                 ],
             ],
             'myresearch-ziskej-ticket-cancel' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/ZiskejTicket/[:eppnDomain]/[:ticketId]/Cancel',
                     'constraints' => [
                         'eppnDomain'     => '.*',
                         'ticketId'     => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'MyResearchZiskej',
                         'action' => 'TicketCancel'
                     ],
                 ],
             ],
             'myresearch-ziskej-message-post' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/MyResearch/ZiskejTicket/[:eppnDomain]/[:ticketId]/Message',
                     'constraints' => [
                         'eppnDomain'     => '.*',
                         'ticketId'     => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'MyResearchZiskej',
                         'action' => 'TicketMessage'
                     ],
                 ],
             ],
             'ziskej-order' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Record/[:id]/ZiskejOrder/:eppnDomain',
                     'constraints' => [
                         'id' => '.*',
                         'eppnDomain' => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'Record',
                         'action' => 'ZiskejOrder'
                     ],
                 ],
             ],
             'ziskej-order-post' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Record/[:id]/ZiskejOrderPost',
                     'constraints' => [
                         'id' => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'Record',
                         'action' => 'ZiskejOrderPost'
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
             'ziskej-order-finished' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Ziskej/Finished/:eppnDomain/:ticketId',
                     'constraints' => [
                         'eppnDomain' => '.*',
                         'ticketId' => '.*',
                     ],
                     'defaults' => [
                         'controller' => 'Ziskej',
                         'action' => 'Finished'
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
            \KnihovnyCz\Controller\MyResearchController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\RecordController::class => \VuFind\Controller\AbstractBaseWithConfigFactory::class,
            \KnihovnyCz\Controller\SearchController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\MyResearchZiskejController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\ZiskejController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\ZiskejAdminController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\HoldsController::class => \VuFind\Controller\HoldsControllerFactory::class,
            \KnihovnyCz\Controller\ContentController::class => \KnihovnyCz\Controller\ContentControllerFactory::class,
        ],
        'aliases' => [
            'Inspiration' => \KnihovnyCz\Controller\InspirationController::class,
            'PortalPage' => \KnihovnyCz\Controller\PortalPageController::class,
            'Wayf' => \KnihovnyCz\Controller\WayfController::class,
            'LibraryCards' => \KnihovnyCz\Controller\LibraryCardsController::class,
            'ZiskejAdmin' => \KnihovnyCz\Controller\ZiskejAdminController::class,
            'Ziskej' => \KnihovnyCz\Controller\ZiskejController::class,
            'MyResearchZiskej' => \KnihovnyCz\Controller\MyResearchZiskejController::class,
            'MyResearch' => \KnihovnyCz\Controller\MyResearchController::class,
            \VuFind\Controller\RecordController::class => \KnihovnyCz\Controller\RecordController::class,
            \VuFind\Controller\SearchController::class => \KnihovnyCz\Controller\SearchController::class,
            \VuFind\Controller\HoldsController::class => \KnihovnyCz\Controller\HoldsController::class,
            \VuFind\Controller\ContentController::class => \KnihovnyCz\Controller\ContentController::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'KnihovnyCz\Controller\Plugin\Holds' => 'VuFind\Controller\Plugin\AbstractRequestBaseFactory',
            'KnihovnyCz\Controller\Plugin\FlashRedirect' => 'VuFind\Controller\Plugin\AbstractRequestBaseFactory',
            'KnihovnyCz\Controller\Plugin\ResultScroller' => \KnihovnyCz\Controller\Plugin\ResultScrollerFactory::class,
        ],
        'aliases' => [
            'holds' => 'KnihovnyCz\Controller\Plugin\Holds',
            'flashRedirect' => 'KnihovnyCz\Controller\Plugin\FlashRedirect',
            'resultScroller' => 'KnihovnyCz\Controller\Plugin\ResultScroller',
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
                    \KnihovnyCz\RecordDriver\EDS::class => \VuFind\RecordDriver\NameBasedConfigFactory::class,
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
                    'dedupedrecords' => \KnihovnyCz\RecordTab\DedupedRecords::class,
                    'edsavailability' => \KnihovnyCz\RecordTab\EdsAvailability::class,
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
                    \KnihovnyCz\ContentBlock\Inspiration::class => \KnihovnyCz\ContentBlock\AbstractDbAwaredRecordIdsFactory::class,
                    \KnihovnyCz\ContentBlock\UserList::class => \KnihovnyCz\ContentBlock\AbstractDbAwaredRecordIdsFactory::class,
                    \KnihovnyCz\ContentBlock\TemplateBased::class => \KnihovnyCz\ContentBlock\TemplateBasedFactory::class,
                ],
                'aliases' => [
                    'documenttypes' => \KnihovnyCz\ContentBlock\DocumentTypes::class,
                    'inspiration' => \KnihovnyCz\ContentBlock\Inspiration::class,
                    'userlist' => \KnihovnyCz\ContentBlock\UserList::class,
                    \VuFind\ContentBlock\TemplateBased::class => \KnihovnyCz\ContentBlock\TemplateBased::class,
                ]
            ],
            'db_row' => [
                'factories' => [
                    \KnihovnyCz\Db\Row\Config::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\InstConfigs::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\InstSources::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\Widget::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\WidgetContent::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\User::class => \VuFind\Db\Row\UserFactory::class,
                    \KnihovnyCz\Db\Row\UserCard::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\CsrfToken::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\UserList::class => \VuFind\Db\Row\UserListFactory::class
                ],
                'aliases' => [
                    \VuFind\Db\Row\User::class => \KnihovnyCz\Db\Row\User::class,
                    \VuFind\Db\Row\UserCard::class => \KnihovnyCz\Db\Row\UserCard::class,
                    \VuFind\Db\Row\UserList::class => \KnihovnyCz\Db\Row\UserList::class,
                ]
            ],
            'db_table' => [
                'factories' => [
                    \KnihovnyCz\Db\Table\Config::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\InstConfigs::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\InstSources::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\User::class => \VuFind\Db\Table\UserFactory::class,
                    \KnihovnyCz\Db\Table\UserCard::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\Widget::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\WidgetContent::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\CsrfToken::class => \VuFind\Db\Table\GatewayFactory::class,
                ],
                'aliases' => [
                    \VuFind\Db\Table\User::class => \KnihovnyCz\Db\Table\User::class,
                    \VuFind\Db\Table\UserCard::class => \KnihovnyCz\Db\Table\UserCard::class,
                ],
            ],
            'ils_driver' => [
                'factories' => [
                    \KnihovnyCz\ILS\Driver\KohaRest1905::class => \VuFind\ILS\Driver\DriverWithDateConverterFactory::class,
                    \KnihovnyCz\ILS\Driver\MultiBackend::class => \KnihovnyCz\ILS\Driver\MultiBackendFactory::class,
                    \KnihovnyCz\ILS\Driver\XCNCIP2::class => \VuFind\ILS\Driver\DriverWithDateConverterFactory::class,
                    \KnihovnyCz\ILS\Driver\Aleph::class => \VuFind\ILS\Driver\AlephFactory::class,
                    \KnihovnyCz\ILS\Driver\NoILS::class => \VuFind\ILS\Driver\NoILSFactory::class,
                ],
                'aliases' => [
                    'koharest1905' => \KnihovnyCz\ILS\Driver\KohaRest1905::class,
                    'multibackend' => \KnihovnyCz\ILS\Driver\MultiBackend::class,
                    'xcncip2' => \KnihovnyCz\ILS\Driver\XCNCIP2::class,
                    'aleph' => \KnihovnyCz\ILS\Driver\Aleph::class,
                    'noils' => \KnihovnyCz\ILS\Driver\NoILS::class,
                ],
            ],
            'content_toc' => [
                'factories' => [
                    \KnihovnyCz\Content\TOC\ObalkyKnih::class => \VuFind\Content\ObalkyKnihContentFactory::class
                ],
                'aliases' => [
                    'obalkyknih' => \KnihovnyCz\Content\TOC\ObalkyKnih::class
                ]
            ],
            'ajaxhandler' => [
                'factories' => [
                    \KnihovnyCz\AjaxHandler\Edd::class => \KnihovnyCz\AjaxHandler\EddFactory::class,
                    \KnihovnyCz\AjaxHandler\GetCitation::class => \KnihovnyCz\AjaxHandler\GetCitationFactory::class,
                    \KnihovnyCz\AjaxHandler\GetHolding::class => \KnihovnyCz\AjaxHandler\GetHoldingFactory::class,
                    \KnihovnyCz\AjaxHandler\GetObalkyKnihCoverWithoutSolr::class => \KnihovnyCz\AjaxHandler\GetObalkyKnihCoverWithoutSolrFactory::class,
                    \KnihovnyCz\AjaxHandler\GetACSuggestions::class => \KnihovnyCz\AjaxHandler\GetACSuggestionsFactory::class,
                    \KnihovnyCz\AjaxHandler\HarvestWidgetsContents::class => \KnihovnyCz\AjaxHandler\HarvestWidgetsContentsFactory::class,
                    \KnihovnyCz\AjaxHandler\Sfx::class => \KnihovnyCz\AjaxHandler\SfxFactory::class,
                ],
                'aliases' => [
                    'edd' => \KnihovnyCz\AjaxHandler\Edd::class,
                    'getcitation' => \KnihovnyCz\AjaxHandler\GetCitation::class,
                    'getHolding' => \KnihovnyCz\AjaxHandler\GetHolding::class,
                    'getObalkyKnihCoverWithoutSolr' => \KnihovnyCz\AjaxHandler\GetObalkyKnihCoverWithoutSolr::class,
                    'getACSuggestions' => \KnihovnyCz\AjaxHandler\GetACSuggestions::class,
                    'harvestWidgetsContents' => \KnihovnyCz\AjaxHandler\HarvestWidgetsContents::class,
                    'sfx' => \KnihovnyCz\AjaxHandler\Sfx::class,
                ],
            ],
            'related' => [
                'invokables' => [
                    \KnihovnyCz\Related\SolrField::class,
                ],
                'aliases' => [
                    'solrfield' => \KnihovnyCz\Related\SolrField::class,
                ],
            ],
            'search_backend' => [
                'factories' => [
                    'Solr' => \KnihovnyCz\Search\Factory\SolrDefaultBackendFactory::class,
                ],
            ],
            'autocomplete' => [
                'factories' => [
                    \KnihovnyCz\Autocomplete\SolrPrefix::class => \VuFind\Autocomplete\SolrFactory::class,
                ],
                'aliases' => [
                    'solrprefix' => \KnihovnyCz\Autocomplete\SolrPrefix::class,
                ]
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            \KnihovnyCz\Service\CitaceProService::class => \KnihovnyCz\Service\CitaceProServiceFactory::class,
            \KnihovnyCz\Config\PluginManager::class => \KnihovnyCz\Config\PluginManagerFactory::class,
            \KnihovnyCz\Content\ObalkyKnihService::class => \VuFind\Content\ObalkyKnihServiceFactory::class,
            \KnihovnyCz\Content\PageLocator::class => \KnihovnyCz\Content\PageLocatorFactory::class,
            \KnihovnyCz\ILS\Service\SolrIdResolver::class => \KnihovnyCz\ILS\Service\SolrIdResolverFactory::class,
            \KnihovnyCz\Service\WayfFilterGenerator::class => \KnihovnyCz\Service\WayfFilterGeneratorFactory::class,
            \Mzk\ZiskejApi\Api::class => \KnihovnyCz\Ziskej\ZiskejApiFactory::class,
            \KnihovnyCz\Ziskej\ZiskejEdd::class => \KnihovnyCz\Ziskej\ZiskejFactory::class,
            \KnihovnyCz\Ziskej\ZiskejMvs::class => \KnihovnyCz\Ziskej\ZiskejFactory::class,
            \KnihovnyCz\Auth\Manager::class => \VuFind\Auth\ManagerFactory::class,
            \KnihovnyCz\Autocomplete\Suggester::class => \VuFind\Autocomplete\SuggesterFactory::class,
            'VuFindHttp\HttpService' => \KnihovnyCz\Service\HttpServiceFactory::class,
            \KnihovnyCz\Service\GuzzleHttpService::class => \KnihovnyCz\Service\GuzzleHttpServiceFactory::class,
            \KnihovnyCz\Validator\DatabaseCsrf::class => \KnihovnyCz\Validator\DatabaseCsrfFactory::class,
            \KnihovnyCz\ILS\Connection::class => \VuFind\ILS\ConnectionFactory::class,
            \KnihovnyCz\Date\Converter::class => \VuFind\Service\DateConverterFactory::class,
            \KnihovnyCz\Search\SearchRunner::class => \VuFind\Search\SearchRunnerFactory::class,
        ],
        'aliases' => [
            \VuFind\Config\PluginManager::class => \KnihovnyCz\Config\PluginManager::class,
            \VuFind\Content\ObalkyKnihService::class => \KnihovnyCz\Content\ObalkyKnihService::class,
            \VuFind\Content\PageLocator::class => \KnihovnyCz\Content\PageLocator::class,
            \VuFind\Auth\Manager::class => \KnihovnyCz\Auth\Manager::class,
            \VuFind\Autocomplete\Suggester::class => \KnihovnyCz\Autocomplete\Suggester::class,
            'Laminas\Validator\Csrf' => \KnihovnyCz\Validator\DatabaseCsrf::class,
            'VuFind\Validator\Csrf' => \KnihovnyCz\Validator\DatabaseCsrf::class,
            \VuFind\Validator\CsrfInterface::class => \KnihovnyCz\Validator\DatabaseCsrf::class,
            \VuFind\ILS\Connection::class => \KnihovnyCz\ILS\Connection::class,
            \VuFind\Date\Converter::class => \KnihovnyCz\Date\Converter::class,
            \VuFind\Search\SearchRunner::class => \KnihovnyCz\Search\SearchRunner::class,
            'VuFind\SearchRunner' => \KnihovnyCz\Search\SearchRunner::class,
        ],
        'invokables' => [
            \Symfony\Component\Filesystem\Filesystem::class,
            \KnihovnyCz\Service\GoogleBooksLinkService::class,
            \KnihovnyCz\Service\ZboziLinkService::class,
            \KnihovnyCz\ILS\Logic\Holdings::class,
        ]
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
    'Libraries/Advanced' => 'Search2/Advanced',
    'Libraries/FacetList' => 'Search2/FacetList',
    'Libraries/Home' => 'Search2/Home',
    'Libraries/Results' => 'Search2/Results',
    'Libraries/Versions' => 'Search2/Versions',
    'MyResearch/DeleteUser' => 'MyResearch/DeleteUser',
    'MyResearch/FinesAjax' => 'MyResearch/FinesAjax',
    'MyResearch/ProfileAjax' => 'MyResearch/ProfileAjax',
    'MyResearch/CheckedoutAjax' => 'MyResearch/CheckedoutAjax',
    'MyResearch/HistoricloansAjax' => 'MyResearch/HistoricloansAjax',
    'Holds/ListAjax' => 'Holds/ListAjax',
    'MyResearchZiskej/ListAjax' => 'MyResearchZiskej/ListAjax',
    'MyResearch/LogoutWarning' => 'MyResearch/LogoutWarning',
    'Search/Embedded' => 'Search/Embedded',
];

$routeGenerator = new \KnihovnyCz\Route\RouteGenerator();
$routeGenerator->addRecordRoutes($config, $recordRoutes);
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;
