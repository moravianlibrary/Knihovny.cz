<?php
/**
 * VuFind Search Runner
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace KnihovnyCz\Search;

use Laminas\Stdlib\Parameters;
use VuFind\Search\SearchRunner as Base;
use VuFind\Search\Solr\AbstractErrorListener as ErrorListener;

/**
 * VuFind Search Runner
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class SearchRunner extends Base
{
    /**
     * Run the search.
     *
     * @param array|Parameters $rawRequest    Incoming parameters for search
     * @param string           $searchClassId Type of search to perform
     * @param mixed            $setupCallback Optional callback for setting up params
     * and attaching listeners; if provided, will be passed three parameters:
     * this object, the search parameters object, and a unique identifier for
     * the current running search.
     * @param string           $lastView      Last valid view parameter loaded
     * from a previous search (optional; used for view persistence).
     *
     * @return \VuFind\Search\Base\Results
     *
     * @throws \VuFindSearch\Backend\Exception\BackendException
     */
    public function run(
        $rawRequest,
        $searchClassId = 'Solr',
        $setupCallback = null,
        $lastView = null
    ) {
        // Increment the ID counter, then save the current value to a variable;
        // since events within this run could theoretically trigger additional
        // runs of the SearchRunner, we can't rely on the property value past
        // this point!
        $this->searchId++;
        $runningSearchId = $this->searchId;

        // Format the request object:
        $request = $rawRequest instanceof Parameters
            ? $rawRequest
            : new Parameters(is_array($rawRequest) ? $rawRequest : []);

        // Set up the search:
        $results = $this->resultsManager->get($searchClassId);
        $params = $results->getParams();
        $params->setLastView($lastView);
        $params->initFromRequest($request);

        if (is_callable($setupCallback)) {
            $setupCallback($this, $params, $runningSearchId);
        }

        // Trigger the "configuration done" event.
        $this->getEventManager()->trigger(
            self::EVENT_CONFIGURED,
            $this,
            compact('params', 'request', 'runningSearchId')
        );

        if (isset($rawRequest['enabledFacets'])) {
            $facets = $rawRequest['enabledFacets'];
            $facetConfig = $params->getFacetConfig();
            $params->resetFacetConfig();
            foreach ($facetConfig as $facet => $alias) {
                if (in_array($facet, $facets)) {
                    $params->addFacet($facet);
                }
            }
        }

        // Attempt to perform the search; if there is a problem, inspect any Solr
        // exceptions to see if we should communicate to the user about them.
        try {
            // Explicitly execute search within controller -- this allows us to
            // catch exceptions more reliably:
            $results->performAndProcessSearch();
        } catch (\VuFindSearch\Backend\Exception\BackendException $e) {
            if ($e->hasTag(ErrorListener::TAG_PARSER_ERROR)) {
                // We need to create and process an "empty results" object to
                // ensure that recommendation modules and templates behave
                // properly when displaying the error message.
                $results = $this->resultsManager->get('EmptySet');
                $results->setParams($params);
                $results->performAndProcessSearch();
            } else {
                throw $e;
            }
        }

        // Trigger the "search completed" event.
        $this->getEventManager()->trigger(
            self::EVENT_COMPLETE,
            $this,
            compact('results', 'runningSearchId')
        );

        return $results;
    }
}
