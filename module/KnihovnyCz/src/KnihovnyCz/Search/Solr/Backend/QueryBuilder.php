<?php

/**
 * VuFind QueryBuilder.
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2023.
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
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */

namespace KnihovnyCz\Search\Solr\Backend;

use VuFindSearch\ParamBag;
use VuFindSearch\Query\AbstractQuery;
use VuFindSearch\Query\Query;
use VuFindSearch\Query\QueryGroup;

/**
 * SOLR QueryBuilder.
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class QueryBuilder extends \VuFindSearch\Backend\Solr\QueryBuilder
{
    protected $externalQueryParameters = null;

    /**
     * Return SOLR search parameters based on a user query and params.
     * Non-reentrant method.
     *
     * @param AbstractQuery $query               User query
     * @param boolean       $switchToParentQuery Switch to parent query
     *
     * @return ParamBag
     */
    public function build(AbstractQuery $query, $switchToParentQuery = false)
    {
        $this->externalQueryParameters->reset();
        $this->externalQueryParameters
            ->setSwitchToParentQuery($switchToParentQuery);
        $params = new ParamBag();

        // Add spelling query if applicable -- note that we must set this up before
        // we process the main query in order to avoid unwanted extra syntax:
        if ($this->createSpellingQuery) {
            $params->set(
                'spellcheck.q',
                $this->getLuceneHelper()->extractSearchTerms($query->getAllTerms())
            );
        }

        if ($query instanceof QueryGroup) {
            $finalQuery = $this->reduceQueryGroup($query);
        } else {
            // Clone the query to avoid modifying the original user-visible query
            $finalQuery = clone $query;
            $finalQuery->setString($this->getNormalizedQueryString($query));
        }
        $string = $finalQuery->getString() ?: '*:*';

        // Highlighting is enabled if we have a field list set.
        $highlight = !empty($this->fieldsToHighlight);

        if ($handler = $this->getSearchHandler($finalQuery->getHandler(), $string)) {
            $string = $handler->preprocessQueryString($string);
            if (
                !$handler->hasExtendedDismax()
                && $this->getLuceneHelper()->containsAdvancedLuceneSyntax($string)
            ) {
                $string = $this->createAdvancedInnerSearchString($string, $handler);
                if ($handler->hasDismax()) {
                    $oldString = $string;
                    $string = $handler->createBoostQueryString($string);

                    // If a boost was added, we don't want to highlight based on
                    // the boost query, so we should use the non-boosted version:
                    if ($highlight && $oldString != $string) {
                        $params->set('hl.q', $oldString);
                    }
                }
            } else {
                $string = $handler->createSimpleQueryString($string);
            }
        }
        // Set an appropriate highlight field list when applicable:
        if ($highlight) {
            $filter = $handler ? $handler->getAllFields() : [];
            $params->add('hl.fl', $this->getFieldsToHighlight($filter));
        }
        $params->set('q', $string);
        if ($switchToParentQuery) {
            $childrenQuery = preg_replace('/{!child [^}]+}/', '*:*', $string);
            $params->set('childrenQuery', $childrenQuery);
        }

        // Handle any extra parameters:
        foreach ($this->globalExtraParams as $extraParam) {
            if (empty($extraParam['param']) || empty($extraParam['value'])) {
                continue;
            }
            if (!$this->checkParamConditions($query, $extraParam['conditions'] ?? [])) {
                continue;
            }
            foreach ((array)$extraParam['value'] as $value) {
                $params->add($extraParam['param'], $value);
            }
        }

        foreach ($this->externalQueryParameters->getParameters() as $key => $search) {
            $params->add($key, $search);
        }
        return $params;
    }

    /**
     * Set query builder search specs.
     *
     * @param array $specs Search specs
     *
     * @return void
     */
    public function setSpecs(array $specs)
    {
        foreach ($specs as $handler => $spec) {
            if ('GlobalExtraParams' === $handler) {
                $this->globalExtraParams = $spec;
                continue;
            }
            if (isset($spec['ExactSettings'])) {
                $this->exactSpecs[strtolower($handler)] = new SearchHandler(
                    $spec['ExactSettings'],
                    $this->defaultDismaxHandler,
                    $this->getExternalQueryParameters()
                );
                unset($spec['ExactSettings']);
            }
            $this->specs[strtolower($handler)]
                = new SearchHandler(
                    $spec,
                    $this->defaultDismaxHandler,
                    $this->getExternalQueryParameters()
                );
        }
    }

    /**
     * Get external parameters
     *
     * @return \KnihovnyCz\Search\Solr\Backend\ExternalQueryParameters
     */
    public function getExternalQueryParameters()
    {
        if ($this->externalQueryParameters == null) {
            $this->externalQueryParameters = new ExternalQueryParameters();
        }
        return $this->externalQueryParameters;
    }
}
