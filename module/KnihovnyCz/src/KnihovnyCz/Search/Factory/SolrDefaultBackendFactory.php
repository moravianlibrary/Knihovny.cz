<?php

/**
 * Factory for the default SOLR backend.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2013.
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
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace KnihovnyCz\Search\Factory;

use VuFind\Search\Factory\SolrDefaultBackendFactory
    as ParentSolrDefaultBackendFactory;
use VuFindSearch\Backend\BackendInterface;
use VuFindSearch\Backend\Solr\Backend;
use VuFindSearch\Backend\Solr\Connector;
use VuFindSearch\Backend\Solr\Response\Json\RecordCollectionFactory;

use KnihovnyCz\Search\Solr\MZKDeduplicationListener;

/**
 * Factory for the default SOLR backend.
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class SolrDefaultBackendFactory extends ParentSolrDefaultBackendFactory
{
    /**
     * Method for creating a record driver.
     *
     * @var string
     */
    protected $createRecordMethod = 'getSolrRecord';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get a deduplication listener for the backend
     *
     * @param BackendInterface $backend Search backend
     * @param bool             $enabled Whether deduplication is enabled
     *
     * @return MZKDeduplicationListener
     */
    protected function getDeduplicationListener(BackendInterface $backend,
        $enabled
    ) {
        $searchConfig = $this->config->get($this->searchConfig);
        $facetConfig = $this->config->get($this->facetConfig);
        $authManager = $this->serviceLocator->get('VuFind\AuthManager');
        return new MZKDeduplicationListener(
            $backend,
            $authManager,
            $searchConfig,
            $facetConfig,
            $enabled
        );
    }

}
