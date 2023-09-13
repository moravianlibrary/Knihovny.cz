<?php

/**
 * Solr Prefix Autocomplete Module
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2022.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @category Knihovny.cz
 * @package  Autocomplete
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:autosuggesters Wiki
 */

namespace KnihovnyCz\Autocomplete;

use VuFind\Search\Results\PluginManager as ResultsManager;

use function count;
use function is_object;

/**
 * Solr autocomplete module with prefix queries using edge N-gram filter
 *
 * This class provides suggestions by using the local Solr index.
 *
 * @category Knihovny.cz
 * @package  Autocomplete
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:autosuggesters Wiki
 */
class SolrPrefixHandler implements \VuFind\Autocomplete\AutocompleteInterface
{
    /**
     * Results manager
     *
     * @var \VuFind\Search\Results\PluginManager
     */
    protected $resultsManager;

    /**
     * Search object
     *
     * @var \VuFind\Search\Solr\Results
     */
    protected $searchObject;

    /**
     * Search class id
     *
     * @var string
     */
    protected $searchClassId = 'Solr';

    /**
     * Search handler to use
     *
     * @var string
     */
    protected $searchHandler;

    /**
     * Facet field
     *
     * @var string
     */
    protected $facetField;

    /**
     * Facet limit, can be overridden in subclasses
     *
     * @var int
     */
    protected $limit = 10;

    /**
     * Filters to apply to Solr search
     *
     * @var array
     */
    protected $filters = [];

    /**
     * Search type to use
     *
     * @var int
     */
    protected $type = 'AllFields';

    /**
     * Filter for suggested terms
     *
     * @var SuggestionFilter
     */
    protected $suggestionFilter;

    /**
     * Constructor
     *
     * @param ResultsManager   $results Results plugin manager
     * @param SuggestionFilter $filter  Suggestion filter
     */
    public function __construct(
        \VuFind\Search\Results\PluginManager $results,
        SuggestionFilter $filter
    ) {
        $this->resultsManager = $results;
        $this->suggestionFilter = $filter;
        $this->limit = 30;
    }

    /**
     * Get suggestions
     *
     * This method returns an array of strings matching the user's query for
     * display in the autocomplete box.
     *
     * @param string $query The user query
     *
     * @return array        The suggestions for the provided query
     */
    public function getSuggestions($query)
    {
        if (!is_object($this->searchObject)) {
            throw new \Exception('Please set configuration first.');
        }

        $results = [];
        try {
            $params = $this->searchObject->getParams();
            $params->setBasicSearch($query, $this->searchHandler);
            $params->addFacet($this->facetField);
            $params->setLimit(0);
            $params->setFacetLimit($this->limit);
            foreach ($this->filters as $current) {
                $params->addFilter($current);
            }
            $options = $params->getOptions();
            $options->disableHighlighting();
            $options->spellcheckEnabled(false);
            $this->searchObject->getResults();
            $facets = $this->searchObject->getFacetList();
            if (isset($facets[$this->facetField]['list'])) {
                foreach ($facets[$this->facetField]['list'] as $filter) {
                    $results[] = $filter['value'];
                }
            }
        } catch (\Exception $e) {
            // Ignore errors -- just return empty results if we must.
        }
        $results = $this->suggestionFilter->filter($query, array_unique($results));
        foreach ($results as &$result) {
            $result['type'] = $this->type;
        }
        return array_splice($results, 0, 10);
    }

    /**
     * Set configuration
     *
     * Set parameters that affect the behavior of the autocomplete handler.
     * These values normally come from the search configuration file.
     *
     * @param string $params Parameters to set
     *
     * @return void
     */
    public function setConfig($params)
    {
        $configFields = explode(':', $params, 3);
        $this->searchHandler = $configFields[0];
        $this->facetField = $configFields[1];
        if (count($configFields) > 2) {
            $this->searchClassId = $configFields[2];
        }
        $this->initSearchObject();
    }

    /**
     * Add filters (in addition to the configured ones)
     *
     * @param array $filters Filters to add
     *
     * @return void
     */
    public function addFilters($filters)
    {
        $this->filters += $filters;
    }

    /**
     * Initialize the search object used for finding recommendations.
     *
     * @return void
     */
    protected function initSearchObject()
    {
        // Build a new search object:
        $this->searchObject = $this->resultsManager->get($this->searchClassId);
        $this->searchObject->getOptions()->spellcheckEnabled(false);
        $this->searchObject->getOptions()->disableHighlighting();
    }
}
