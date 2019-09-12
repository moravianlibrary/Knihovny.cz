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
                    'KnihovnyCz\RecordDriver\SolrAuthority' => 'KnihovnyCz\RecordDriver\SolrDefaultFactory',
                    'KnihovnyCz\RecordDriver\SolrDefault' => 'KnihovnyCz\RecordDriver\SolrDefaultFactory',
                    'KnihovnyCz\RecordDriver\SolrDictionary' => 'KnihovnyCz\RecordDriver\SolrDefaultFactory',
                    'KnihovnyCz\RecordDriver\SolrDublinCore' => 'KnihovnyCz\RecordDriver\SolrDefaultFactory',
                    'KnihovnyCz\RecordDriver\SolrLibrary' => 'KnihovnyCz\RecordDriver\SolrLibraryFactory',
                    'KnihovnyCz\RecordDriver\SolrMarc' => 'KnihovnyCz\RecordDriver\SolrDefaultFactory',
                    'KnihovnyCz\RecordDriver\SolrLocal' => 'KnihovnyCz\RecordDriver\SolrDefaultFactory',
                ],
                'aliases' => [
                    'solrauthority' => 'KnihovnyCz\RecordDriver\SolrAuthority',
                    'solrdefault' => 'KnihovnyCz\RecordDriver\SolrDefault',
                    'solrdictionary' => 'KnihovnyCz\RecordDriver\SolrDictionary',
                    'solrdublincore' => 'KnihovnyCz\RecordDriver\SolrDublinCore',
                    'solrlibrary' => 'KnihovnyCz\RecordDriver\SolrLibrary',
                    'VuFind\RecordDriver\SolrMarc' => 'KnihovnyCz\RecordDriver\SolrMarc',
                    'solrlocal' => 'KnihovnyCz\RecordDriver\SolrLocal',
                ],
                'delegators' => [
                    'KnihovnyCz\RecordDriver\SolrMarc' => [
                        'VuFind\RecordDriver\IlsAwareDelegatorFactory',
                    ],
                    'KnihovnyCz\RecordDriver\SolrLocal' => [
                        'VuFind\RecordDriver\IlsAwareDelegatorFactory',
                    ],
                ],
            ],
            'recordtab' => [
                'invokables' => [
                    'edsavailability' => 'KnihovnyCz\RecordTab\EdsAvailability',
                    'eversion' => 'KnihovnyCz\RecordTab\EVersion',
                    'librarybranches' => 'KnihovnyCz\RecordTab\LibraryBranches',
                    'librarycontacts' => 'KnihovnyCz\RecordTab\LibraryContacts',
                    'libraryinfo' => 'KnihovnyCz\RecordTab\LibraryInfo',
                    'libraryservices' => 'KnihovnyCz\RecordTab\LibraryServices',
                    'staffviewdublincore' => 'KnihovnyCz\RecordTab\StaffViewDublinCore',
                    'usercommentsobalkyknih' => 'KnihovnyCz\RecordTab\UserCommentsObalkyKnih',
                    'ziskej' => 'KnihovnyCz\RecordTab\Ziskej',
                ]
            ],
        ],
    ],
];

return $config;
