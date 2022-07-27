<?php
declare(strict_types=1);

/**
 * Factory for the default SOLR backend.
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
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

use KnihovnyCz\Search\Solr\Backend\Connector;
use KnihovnyCz\Search\Solr\Backend\PerformanceLogger;
use KnihovnyCz\Search\Solr\Backend\Response\Json\RecordCollection;
use KnihovnyCz\Search\Solr\ChildDocDeduplicationListener;
use KnihovnyCz\Search\Solr\DeduplicationListener;
use KnihovnyCz\Search\Solr\JsonFacetListener;
use VuFind\Search\Factory\SolrDefaultBackendFactory
    as ParentSolrDefaultBackendFactory;
use VuFindSearch\Backend\Solr\Backend;

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
     * Site configuration file identifier.
     *
     * @var string
     */
    protected $siteConfig = 'config';

    /**
     * Record collection class for RecordCollectionFactory
     *
     * @var string
     */
    protected $recordCollectionClass = RecordCollection::class;

    /**
     * Solr connector class
     *
     * @var string
     */
    protected $connectorClass = Connector::class;

    /**
     * Create listeners.
     *
     * @param Backend $backend Backend
     *
     * @return void
     */
    protected function createListeners(Backend $backend)
    {
        parent::createListeners($backend);
        $events = $this->serviceLocator->get('SharedEventManager');
        $this->getJsonFacetListener($backend)->attach($events);
    }

    /**
     * Get a JSON facet listener for the backend
     *
     * @param Backend $backend Search backend
     *
     * @return JsonFacetListener
     */
    protected function getJsonFacetListener(Backend $backend)
    {
        $listener = new JsonFacetListener(
            $backend,
            $this->serviceLocator,
            $this->searchConfig,
            $this->facetConfig
        );
        $listener->setLogger($this->logger);
        return $listener;
    }

    /**
     * Get a deduplication listener for the backend
     *
     * @param Backend $backend Search backend
     * @param bool    $enabled Whether deduplication is enabled
     *
     * @return DeduplicationListener
     */
    protected function getDeduplicationListener(
        Backend $backend,
        $enabled
    ) {
        $class = DeduplicationListener::class;
        $search = $this->config->get($this->searchConfig);
        $type = $search->Records->deduplication_type ?? null;
        if ($type == 'child') {
            $class = ChildDocDeduplicationListener::class;
        }
        return new $class(
            $backend,
            $this->serviceLocator,
            $this->searchConfig,
            $this->facetConfig,
            'datasources',
            $enabled
        );
    }

    /**
     * Create the SOLR connector.
     *
     * @return Connector
     */
    protected function createConnector()
    {
        $connector = parent::createConnector();
        $request = $this->serviceLocator->get('Request');
        $connector->setRequest($request);
        $config = $this->config->get($this->mainConfig);
        $perfLog = $config->Index->perf_log ?? null;
        if ($perfLog != null) {
            $siteConfig = $this->config->get($this->siteConfig);
            $baseUrl = $siteConfig->Site->url ?? '';
            $logger = new PerformanceLogger($perfLog, $baseUrl, $request);
            $connector->setPerformanceLogger($logger);
        }
        return $connector;
    }
}
