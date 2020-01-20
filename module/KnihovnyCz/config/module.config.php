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
                    \KnihovnyCz\RecordDriver\SolrLocal::class => \KnihovnyCz\RecordDriver\SolrDefaultFactory::class,
                ],
                'aliases' => [
                    'solrauthority' => \KnihovnyCz\RecordDriver\SolrAuthority::class,
                    'solrdefault' => \KnihovnyCz\RecordDriver\SolrDefault::class,
                    'solrdictionary' => \KnihovnyCz\RecordDriver\SolrDictionary::class,
                    'solrdublincore' => \KnihovnyCz\RecordDriver\SolrDublinCore::class,
                    'solrlibrary' => \KnihovnyCz\RecordDriver\SolrLibrary::class,
                    \VuFind\RecordDriver\SolrMarc::class => \KnihovnyCz\RecordDriver\SolrMarc::class,
                    'solrlocal' => \KnihovnyCz\RecordDriver\SolrLocal::class,
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
        ],
    ],
];

return $config;
