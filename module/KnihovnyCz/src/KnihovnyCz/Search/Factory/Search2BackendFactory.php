<?php

declare(strict_types=1);

namespace KnihovnyCz\Search\Factory;

/**
 * Factory for a second Solr backend
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Search2BackendFactory extends SolrDefaultBackendFactory
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->mainConfig = $this->searchConfig = $this->facetConfig = 'Search2';
        $this->searchYaml = 'searchspecs2.yaml';
    }

    /**
     * Get the callback for creating a record.
     *
     * Returns a callable or null to use RecordCollectionFactory's default method.
     *
     * @return callable|null
     */
    protected function getCreateRecordCallback(): ?callable
    {
        $manager = $this->serviceLocator
            ->get(\VuFind\RecordDriver\PluginManager::class);
        return [$manager, 'getSearch2Record'];
    }
}
