<?php

/**
 * Solr json facet listener.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2013.
 * Copyright (C) The National Library of Finland 2014.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Search
 * @author   Vaclav Rosecky <xrosecky@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace KnihovnyCz\Search\Solr;

use Laminas\EventManager\EventInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Psr\Container\ContainerInterface;
use VuFindSearch\Backend\Solr\Backend;
use VuFindSearch\Service;

/**
 * Solr hierarchical facet handling listener.
 *
 * @category VuFind2
 * @package  Search
 * @author   Vaclav Rosecky <xrosecky@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class JsonFacetListener implements \Laminas\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;

    protected const SOLR_LOCAL_PARAMS = "/(\\{[^\\}]*\\})*(\S+)/";

    protected const DEFAULT_FACET_LIMIT = -1;

    /**
     * Backend.
     *
     * @var Backend
     */
    protected $backend;

    /**
     * Nested facets
     *
     * @var array
     */
    protected $nestedFacets = [];

    /**
     * Or facets
     *
     * @var array
     */
    protected $orFacets = [];

    /**
     * Show all values for facets (ie. values with zero count)
     *
     * @var array
     */
    protected $zeroCountFacets = [];

    /**
     * All facets are ORed
     *
     * @var bool
     */
    protected $allFacetsAreOr = false;

    /**
     * Use JSON API for all facets
     *
     * @var bool
     */
    protected $enabledForAllFacets = false;

    /**
     * Facet method to use
     *
     * @var string
     */
    protected $facetMethod = 'smart';

    /**
     * Return parent count - make faceting slower
     *
     * @var bool
     */
    protected $parentCount = true;

    /**
     * Hidden filters to apply
     *
     * @var array
     */
    protected $hiddenFilters = [];

    /**
     * Is enabled?
     *
     * @var boolean
     */
    protected $enabled = false;

    /**
     * Child filter to apply to nested facets
     *
     * @var string
     */
    protected $childFilter = null;

    /**
     * Constructor.
     *
     * @param Backend            $backend        Search backend
     * @param ContainerInterface $serviceLocator Service locator
     * @param string             $searchCfg      Search config file id
     * @param string             $facetCfg       Facet config file id
     */
    public function __construct(
        Backend $backend,
        ContainerInterface $serviceLocator,
        $searchCfg,
        $facetCfg
    ) {
        $this->backend = $backend;
        $config = $serviceLocator->get(\VuFind\Config\PluginManager::class);
        $searchConfig = $config->get($searchCfg);
        $facetConfig = $config->get($facetCfg);
        if (isset($facetConfig->Results_Settings->orFacets)) {
            $this->orFacets = array_map(
                'trim',
                explode(
                    ',',
                    $facetConfig->Results_Settings->orFacets
                )
            );
        }
        if (isset($facetConfig->Results_Settings->zeroCountFacets)) {
            $this->zeroCountFacets = array_map(
                'trim',
                explode(
                    ',',
                    $facetConfig->Results_Settings->zeroCountFacets
                )
            );
        }
        if (!empty($this->orFacets) && $this->orFacets[0] == "*") {
            $this->allFacetsAreOr = true;
        }
        if (($specialFacets = $facetConfig->SpecialFacets) !== null) {
            $this->nestedFacets = isset($specialFacets->nested) ? $specialFacets
                    ->nested->toArray() : [];
            $this->parentCount = $specialFacets->nestedParentCount ?? false;
        }
        $this->enabledForAllFacets = $facetConfig->JSON_API->enabled ?? false;
        $this->facetMethod = $facetConfig->JSON_API->method ?? 'smart';
        $this->enabled = !empty($this->nestedFacets) || $this->enabledForAllFacets;
        if (isset($searchConfig->ChildRecordFilters)) {
            $this->childFilter = implode(
                ' AND ',
                array_values($searchConfig->ChildRecordFilters->toArray())
            );
        }
    }

    /**
     * Attach listener to shared event manager.
     *
     * @param SharedEventManagerInterface $manager Shared event manager
     *
     * @return void
     */
    public function attach(
        SharedEventManagerInterface $manager
    ) {
        $manager->attach(
            'VuFind\Search',
            Service::EVENT_PRE,
            [$this, 'onSearchPre']
        );
    }

    /**
     * Set up JSON API before search
     *
     * @param EventInterface $event Event
     *
     * @return EventInterface
     */
    public function onSearchPre(EventInterface $event)
    {
        if (!$this->enabled) {
            return $event;
        }
        $command = $event->getParam('command');
        if ($command->getTargetIdentifier() !== $this->backend->getIdentifier()) {
            return $event;
        }
        if ($command->getContext() != 'search') {
            return $event;
        }
        $this->process($command);
        return $event;
    }

    /**
     * Transform parameters for JSON API
     *
     * @param \VuFindSearch\Command\CommandInterface $command command
     *
     * @return void
     */
    protected function process($command)
    {
        $params = $command->getSearchParameters();
        if (!$params) {
            return;
        }
        $nestedFilters = $this->transformFacetQueries($params);
        $this->transformFacets($params, $nestedFilters);
    }

    /**
     * Transform facets parameters for JSON API
     *
     * @param \VuFindSearch\ParamBag $params        parameters
     * @param array                  $nestedFilters nested filters
     *
     * @return void
     */
    protected function transformFacets($params, $nestedFilters)
    {
        $jsonFacetData = [];
        $remaining = [];
        if ($params->get('facet.field')) {
            foreach ($params->get('facet.field') as $facetField) {
                $field = $facetField;
                if (preg_match(self::SOLR_LOCAL_PARAMS, $field, $matches)) {
                    $field = $matches[2];
                }
                $isNested = in_array($field, $this->nestedFacets);
                if ($isNested || $this->enabledForAllFacets) {
                    $jsonFacetData[$field] = $this->getFacetConfig(
                        $field,
                        $params,
                        $nestedFilters,
                        $isNested
                    );
                } else {
                    $remaining[] = $facetField;
                }
            }
        }
        if (empty($remaining)) {
            $params->remove('facet.field');
        } else {
            $params->set('facet.field', $remaining);
        }

        if (!empty($jsonFacetData)) {
            $this->getLogger()->info(
                "json.facet: " .
                json_encode($jsonFacetData, JSON_PRETTY_PRINT)
            );
            $params->set('json.facet', json_encode($jsonFacetData));
        }
    }

    /**
     * Return facet parameter
     *
     * @param string                 $field     field
     * @param \VuFindSearch\ParamBag $params    parameters
     * @param string                 $parameter parameter
     * @param string                 $default   default value if parameter is not set
     *
     * @return string
     */
    protected function getFacetParameter(
        $field,
        $params,
        $parameter,
        $default = null
    ) {
        $keys = [
            "f.${field}.facet.${parameter}",
            "facet.${parameter}"
        ];
        foreach ($keys as $key) {
            if ($params->hasParam($key)) {
                return $params->get($key)[0];
            }
        }
        return $default;
    }

    /**
     * Get configuration for nested facet
     *
     * @param string                 $facetField field
     * @param \VuFindSearch\ParamBag $params     parameters
     * @param array                  $filters    filters to apply
     * @param bool                   $nested     nested
     *
     * @return array
     */
    protected function getFacetConfig($facetField, $params, $filters, $nested)
    {
        $limit = $this->getFacetParameter(
            $facetField,
            $params,
            'limit',
            self::DEFAULT_FACET_LIMIT
        );
        $sort = $this->getFacetParameter($facetField, $params, 'sort', 'count');
        $facetConfig = [
            'type' => 'terms',
            'field' => $facetField,
            'limit' => (int)$limit,
            'sort'  => $sort,
        ];
        $offset = $this->getFacetParameter($facetField, $params, 'offset');
        if ($offset != null) {
            $facetConfig['offset'] = $offset;
        }
        if (in_array($facetField, $this->zeroCountFacets)) {
            $facetConfig['mincount'] = 0;
        }
        $facetConfig['method'] = $this->facetMethod;
        if (!$nested) {
            return $facetConfig;
        }
        if ($this->parentCount) {
            $facetConfig['facet'] = [ 'count' => 'unique(_root_)' ];
        }
        $q = 'merged_child_boolean:true';
        $appliedFilters = [];
        foreach ($filters as $field => $filter) {
            if ($facetField != $field) {
                $appliedFilters[] = ' ( ' . $filter . ' ) ';
            }
        }
        if (!empty($appliedFilters)) {
            $q .= ' AND (' . implode(' AND ', $appliedFilters) . ')';
        }
        if ($this->childFilter != null) {
            $q .= ' AND (' . $this->childFilter . ')';
        }
        return [
            'type'   => 'query',
            'q'      => $q,
            'domain' => [ "blockChildren" => "merged_boolean:true" ],
            'facet'  => [
                $facetField => $facetConfig
            ]
        ];
    }

    /**
     * Transform facet queries
     *
     * @param \VuFindSearch\ParamBag $params parameters
     *
     * @return array filters to apply
     */
    protected function transformFacetQueries($params)
    {
        $filters = [];
        $fqs = $params->get('fq');
        $newfqs = [];
        $nestedOrFacets = [];
        foreach ($fqs as $fq) {
            [$field, $query] = explode(":", $fq, 2);
            $localParams = null;
            $matches = [];
            if (preg_match(self::SOLR_LOCAL_PARAMS, $field, $matches)) {
                $localParams = $matches[1];
                $field = $matches[2];
                if (!empty($localParams)) {
                    $fq = $localParams . ' (' . $field . ':' . $query . ')';
                }
            }
            if (in_array($field, $this->nestedFacets)) {
                if ($this->isOrFacet($field)) {
                    $nestedOrFacets[] = $fq;
                }
                $filter = $this->addToLocalParams(
                    $localParams,
                    "parent which='merged_boolean:true'"
                ) . ' '
                    . $field . ':' . $query;
                if ($this->childFilter != null) {
                    $filter .= ' AND (' . $this->childFilter . ')';
                }
                $newfqs[] = $filter;
                $filters[$field][] = $query;
            } else {
                $newfqs[] = $fq;
            }
        }
        if (count($nestedOrFacets) > 1) {
            $newfqs[] = "{!parent which='merged_boolean:true' " .
                "tag=nested_facet_filter}( " . implode(
                    ' AND ',
                    $nestedOrFacets
                ) . " )";
        }
        $this->getLogger()->debug(
            "New fq parameters: " .
            print_r($newfqs, true)
        );
        $params->set('fq', $newfqs);
        $nestedFilters = [];
        foreach ($filters as $field => $values) {
            $operator = $this->isOrFacet($field) ? 'OR' : 'AND';
            $nestedFilters[$field] = implode(' ' . $operator . ' ', $values);
        }
        return $nestedFilters;
    }

    /**
     * Add to local parameters
     *
     * @param string $localParams current local parameters
     * @param string $newParam    new local parameter
     *
     * @return string new parameter
     */
    protected function addToLocalParams($localParams, $newParam)
    {
        $localParams = trim($localParams);
        if ($localParams == null) {
            return "{!" . $newParam . "}";
        } else {
            return rtrim($localParams, "}") . " " . $newParam . "}";
        }
    }

    /**
     * Return if the field is ORed
     *
     * @param $field field name
     *
     * @return bool  is OR facet
     */
    protected function isOrFacet($field)
    {
        return $this->allFacetsAreOr || in_array($field, $this->orFacets);
    }
}
