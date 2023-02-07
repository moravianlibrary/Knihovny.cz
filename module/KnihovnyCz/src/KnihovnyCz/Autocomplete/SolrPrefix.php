<?php

/**
 * Solr Prefix Autocomplete Module
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2021.
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
class SolrPrefix extends \VuFind\Autocomplete\SolrPrefix
{
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
        parent::__construct($results);
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
        $results = parent::getSuggestions($query);
        $results = $this->suggestionFilter->filter($query, $results);
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
        $this->autocompleteField = $configFields[0];
        $this->facetField = $configFields[1];
        if (count($configFields) > 2) {
            $this->searchClassId = $configFields[2];
        }
        $this->initSearchObject();
    }
}
