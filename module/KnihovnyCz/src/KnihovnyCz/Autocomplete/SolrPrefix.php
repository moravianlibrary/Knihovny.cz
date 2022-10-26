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
 * @category VuFind
 * @package  Autocomplete
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:autosuggesters Wiki
 */
namespace KnihovnyCz\Autocomplete;

/**
 * Solr autocomplete module with prefix queries using edge N-gram filter
 *
 * This class provides suggestions by using the local Solr index.
 *
 * @category VuFind
 * @package  Autocomplete
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:autosuggesters Wiki
 */
class SolrPrefix extends \VuFind\Autocomplete\SolrPrefix
{
    /**
     * Constructor
     *
     * @param \VuFind\Search\Results\PluginManager $results Results plugin manager
     */
    public function __construct(\VuFind\Search\Results\PluginManager $results)
    {
        parent::__construct($results);
        $this->resultsManager = $results;
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
        $result = parent::getSuggestions($query);
        $result = $this->filter($query, $result);
        return array_splice($result, 0, 10);
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

    /**
     * Filter suggestions
     *
     * @param string $query   The user query
     * @param array  $results Suggestions to filter
     *
     * @return array Filtered suggestions according to query
     */
    protected function filter($query, $results)
    {
        $filtered = [];
        $normalizedQuery = $this->normalize($query);
        $queryParts = (array)preg_split('/\s+/', $normalizedQuery);
        $queryPartsCount = count($queryParts);

        foreach ($results as $result) {
            $matchedQueryParts = 0;
            foreach ($queryParts as $queryPart) {
                $resultParts = (array)preg_split(
                    '/\s+/',
                    $this->normalize($result)
                );
                foreach ($resultParts as $resultPart) {
                    if (is_string($resultPart)
                        && is_string($queryPart)
                        && stripos($resultPart, $queryPart) !== false
                    ) {
                        $matchedQueryParts++;
                    }
                }
            }
            if ($matchedQueryParts == $queryPartsCount) {
                $filtered[] = $result;
            }
        }

        return $filtered;
    }

    /**
     * Normalize query
     *
     * @param string $query query to normalize
     *
     * @return string normalized query with removed diacritic
     */
    protected function normalize($query)
    {
        return (string)iconv("UTF-8", "ASCII//TRANSLIT", $query);
    }
}
