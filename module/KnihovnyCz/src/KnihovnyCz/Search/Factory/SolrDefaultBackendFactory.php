<?php

declare(strict_types=1);

namespace KnihovnyCz\Search\Factory;

use KnihovnyCz\Search\Solr\Backend\Backend as KnihovnyCzBackend;
use KnihovnyCz\Search\Solr\Backend\Connector as KnihovnyCzConnector;
use KnihovnyCz\Search\Solr\Backend\LuceneSyntaxHelper;
use KnihovnyCz\Search\Solr\Backend\OrQueryRewriter;
use KnihovnyCz\Search\Solr\Backend\PerformanceLogger;
use KnihovnyCz\Search\Solr\Backend\QueryBuilder;
use KnihovnyCz\Search\Solr\Backend\Response\Json\RecordCollection;
use KnihovnyCz\Search\Solr\ChildDocDeduplicationListener;
use KnihovnyCz\Search\Solr\DeduplicationListener;
use KnihovnyCz\Search\Solr\MultiplyingDeduplicationListener;
use KnihovnyCz\Search\Solr\OneChildDocDeduplicationListener;
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
    protected $connectorClass = KnihovnyCzConnector::class;

    /**
     * Solr backend class
     *
     * @var string
     */
    protected $backendClass = KnihovnyCzBackend::class;

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
        $type = $this->getDeduplicationType();
        if ($type == 'child') {
            $class = ChildDocDeduplicationListener::class;
        } elseif ($type == 'one_child') {
            $class = OneChildDocDeduplicationListener::class;
        } elseif ($type == 'multiplying') {
            $class = MultiplyingDeduplicationListener::class;
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

    /**
     * Create the query builder.
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilder()
    {
        $specs   = $this->loadSpecs();
        $config = $this->config->get($this->mainConfig);
        $defaultDismax = $config->Index->default_dismax_handler ?? 'dismax';
        $searchConfig = $this->config->get($this->searchConfig);
        $builder = new QueryBuilder($specs, $defaultDismax, $searchConfig);

        // Configure builder:
        $builder->setLuceneHelper($this->createLuceneSyntaxHelper());

        return $builder;
    }

    /**
     * Return deduplication type to use
     *
     * @return string|null
     */
    protected function getDeduplicationType(): ?string
    {
        $search = $this->config->get($this->searchConfig);
        return $search->Records->deduplication_type ?? null;
    }

    /**
     * Create Lucene syntax helper.
     *
     * @return LuceneSyntaxHelper
     */
    protected function createLuceneSyntaxHelper(): LuceneSyntaxHelper
    {
        $search = $this->config->get($this->searchConfig);
        $caseSensitiveBooleans = $search->General->case_sensitive_bools ?? true;
        $caseSensitiveRanges = $search->General->case_sensitive_ranges ?? true;
        $orQueryFixer = new OrQueryRewriter();
        $orQueryFixer->setLogger($this->logger);
        return new LuceneSyntaxHelper($caseSensitiveBooleans, $caseSensitiveRanges, $orQueryFixer);
    }
}
