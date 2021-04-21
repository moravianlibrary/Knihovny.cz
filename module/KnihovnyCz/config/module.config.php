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
             'inpspiration' => [
                 'type' => \Laminas\Router\Http\Segment::class,
                 'options' => [
                     'route' => '/Inspiration',
                     'defaults' => [
                         'controller' => 'Inspiration',
                         'action' => 'Home'
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
         ],
     ],
    'controllers' => [
        'factories' => [
            \KnihovnyCz\Controller\InspirationController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\PortalPageController::class => \VuFind\Controller\AbstractBaseFactory::class,
            \KnihovnyCz\Controller\WayfController::class =>\VuFind\Controller\AbstractBaseFactory::class,
        ],
        'aliases' => [
            'Inspiration' => \KnihovnyCz\Controller\InspirationController::class,
            'PortalPage' => \KnihovnyCz\Controller\PortalPageController::class,
            'Wayf' => \KnihovnyCz\Controller\WayfController::class,
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
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
                    'usercommentsobalkyknih' => \KnihovnyCz\RecordTab\UserCommentsObalkyKnih::class,
                    'ziskej' => \KnihovnyCz\RecordTab\Ziskej::class,
                ],
            ],
            'contentblock' => [
                'factories' => [
                    \KnihovnyCz\ContentBlock\DocumentTypes::class => \KnihovnyCz\ContentBlock\DocumentTypesFactory::class,
                    \KnihovnyCz\ContentBlock\Inspiration::class => \KnihovnyCz\ContentBlock\InspirationFactory::class,
                    \KnihovnyCz\ContentBlock\UserList::class => \KnihovnyCz\ContentBlock\UserListFactory::class,
                ],
                'aliases' => [
                    'documenttypes' => \KnihovnyCz\ContentBlock\DocumentTypes::class,
                    'inspiration' => \KnihovnyCz\ContentBlock\Inspiration::class,
                    'userlist' => \KnihovnyCz\ContentBlock\UserList::class,
                ]
            ],
            'db_row' => [
                'factories' => [
                    \KnihovnyCz\Db\Row\Config::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\InstConfigs::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\InstSources::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\Widget::class => \VuFind\Db\Row\RowGatewayFactory::class,
                    \KnihovnyCz\Db\Row\WidgetContent::class => \VuFind\Db\Row\RowGatewayFactory::class,
                ],
                'aliases' => [
                    // VuFind\Db\Table\GatewayFactory search for row class by name
                    // We do not need to customize row class for user, so we are
                    // are aliasing back to original one. It has to be string as the
                    // class does not exist
                    'KnihovnyCz\Db\Row\User' => \VuFind\Db\Row\User::class,
                ]
            ],
            'db_table' => [
                'factories' => [
                    \KnihovnyCz\Db\Table\Config::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\InstConfigs::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\InstSources::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\User::class => \VuFind\Db\Table\UserFactory::class,
                    \KnihovnyCz\Db\Table\Widget::class => \VuFind\Db\Table\GatewayFactory::class,
                    \KnihovnyCz\Db\Table\WidgetContent::class => \VuFind\Db\Table\GatewayFactory::class,
                ],
                'aliases' => [
                    \VuFind\Db\Table\User::class => \KnihovnyCz\Db\Table\User::class,
                ],
            ],
            'ils_driver' => [
                'factories' => [
                    \KnihovnyCz\ILS\Driver\MultiBackend::class => \KnihovnyCz\ILS\Driver\MultiBackendFactory::class
                ],
                'aliases' => [
                    'multibackend' => \KnihovnyCz\ILS\Driver\MultiBackend::class,
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
                    \KnihovnyCz\AjaxHandler\UpdateContent::class => \KnihovnyCz\AjaxHandler\UpdateContentFactory::class,
                    \KnihovnyCz\AjaxHandler\Edd::class => \KnihovnyCz\AjaxHandler\EddFactory::class,
                    \KnihovnyCz\AjaxHandler\GetCitation::class => \KnihovnyCz\AjaxHandler\GetCitationFactory::class,
                ],
                'aliases' => [
                    'updateContent' => \KnihovnyCz\AjaxHandler\UpdateContent::class,
                    'edd' => \KnihovnyCz\AjaxHandler\Edd::class,
                    'getcitation' => \KnihovnyCz\AjaxHandler\GetCitation::class,
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
        ],
    ],
    'service_manager' => [
        'factories' => [
            \GitWrapper\GitWorkingCopy::class => \KnihovnyCz\Service\GitFactory::class,
            \KnihovnyCz\Service\CitaceProService::class => \KnihovnyCz\Service\CitaceProServiceFactory::class,
            \KnihovnyCz\Config\PluginManager::class => \KnihovnyCz\Config\PluginManagerFactory::class,
            \KnihovnyCz\Content\ObalkyKnihService::class => \VuFind\Content\ObalkyKnihServiceFactory::class,
            \KnihovnyCz\ILS\Service\SolrIdResolver::class => \KnihovnyCz\ILS\Service\SolrIdResolverFactory::class,
            \KnihovnyCz\Service\WayfFilterGenerator::class => \KnihovnyCz\Service\WayfFilterGeneratorFactory::class,
        ],
        'aliases' => [
            \VuFind\Config\PluginManager::class => \KnihovnyCz\Config\PluginManager::class,
            \VuFind\Content\ObalkyKnihService::class => \KnihovnyCz\Content\ObalkyKnihService::class,
        ],
        'invokables' => [
            \Symfony\Component\Filesystem\Filesystem::class,
            \KnihovnyCz\Service\GoogleBooksLinkService::class,
            \KnihovnyCz\Service\ZboziLinkService::class,
        ]
    ],
];

return $config;
