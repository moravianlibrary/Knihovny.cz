<?php

/**
 * Solr Prefix Autocomplete Module
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2022.
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

use function count;
use function is_object;

/**
 * Solr autocomplete module with prefix queries using edge N-gram filter, results
 * are then sorted by supplied function.
 *
 * This class provides suggestions by using the local Solr index.
 *
 * @category Knihovny.cz
 * @package  Autocomplete
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:autosuggesters Wiki
 */
class SolrPrefixSorted implements \VuFind\Autocomplete\AutocompleteInterface
{
    /**
     * Results manager
     *
     * @var \VuFind\Search\Results\PluginManager
     */
    protected $resultsManager;

    /**
     * Suggestion filter
     *
     * @var SuggestionFilter
     */
    protected $suggestionFilter;

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
     * Autocomplete field
     *
     * @var string
     */
    protected $autocompleteField;

    /**
     * Facet field
     *
     * @var string
     */
    protected $facetField;

    /**
     * Sort function
     *
     * @var string
     */
    protected $sort = null;

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
     * Constructor
     *
     * @param \VuFind\Search\Results\PluginManager $results Results plugin manager
     * @param SuggestionFilter                     $filter  Escape HTML helper
     */
    public function __construct(
        \VuFind\Search\Results\PluginManager $results,
        SuggestionFilter $filter
    ) {
        $this->resultsManager = $results;
        $this->suggestionFilter = $filter;
    }

    /**
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

        $highlighter = new Highlighter($query);
        $results = [];
        try {
            $params = $this->searchObject->getParams();
            $rawQuery = $this->autocompleteField . ':(' .
                $this->mungeQuery($query) . ')';
            $params->setBasicSearch($rawQuery);
            $params->setLimit(0);
            $jsonFacet = [
                'type' => 'terms',
                'limit' => $this->limit,
                'field' => $this->facetField,
            ];
            if ($this->sort != null && !empty(trim($this->sort))) {
                $sortConfig = explode(' ', $this->sort);
                $sortFunction = $sortConfig[0];
                $order = 'desc';
                if (count($sortConfig) > 1) {
                    $order = $sortConfig[1];
                }
                $jsonFacet['sort'] = 'sort_field' . ' ' . $order;
                $jsonFacet['facet'] = [
                    'sort_field' => $sortFunction,
                ];
            }
            $params->addJsonFacet($this->facetField, $jsonFacet);
            $options = $params->getOptions();
            $options->disableHighlighting();
            $options->spellcheckEnabled(false);
            foreach ($this->filters as $current) {
                $this->searchObject->getParams()->addFilter($current);
            }
            $this->searchObject->getResults();
            $filter = [
                $this->facetField => $this->facetField,
            ];
            $facets = $this->searchObject->getFacetList($filter);
            if (isset($facets[$this->facetField]['list'])) {
                foreach ($facets[$this->facetField]['list'] as $filter) {
                    $value = $filter['value'];
                    $results[] = $value;
                }
            }
        } catch (\Exception $e) {
            // Ignore errors -- just return empty results if we must.
        }
        return $this->suggestionFilter->filter($query, $results);
    }

    /**
     * Set parameters that affect the behavior of the autocomplete handler.
     * These values normally come from the search configuration file.
     *
     * @param string $params Parameters to set
     *
     * @return void
     */
    public function setConfig($params)
    {
        $entries = explode(':', $params, 4);
        $this->autocompleteField = $entries[0];
        $this->facetField = $entries[1];
        if (count($entries) > 2) {
            $this->sort = $entries[2];
        }
        if (count($entries) > 3) {
            $this->searchClassId = $entries[3];
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

    /**
     * Process the user query to make it suitable for a Solr query.
     *
     * @param string $query Incoming user query
     *
     * @return string       Processed query
     */
    protected function mungeQuery($query)
    {
        $forbidden = [':', '(', ')', '*', '+', '"'];
        return str_replace($forbidden, ' ', $query);
    }
}
