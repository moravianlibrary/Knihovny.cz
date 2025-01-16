<?php

namespace KnihovnyCz\Search\Solr;

use KnihovnyCz\Geo\Parser;
use VuFind\Search\Solr\HierarchicalFacetHelper;
use VuFindSearch\ParamBag;

/**
 * Solr Search Parameters
 *
 * @category KnihovnyCz
 * @package  Search_Solr
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Params extends \VuFind\Search\Solr\Params
{
    protected const DEFAULT_FACET_LIMIT = -1;

    protected const TREAT_AS_NON_HIERARCHICAL = [
        'region_institution_facet_mv',
        'local_region_institution_facet_mv',
    ];

    /**
     * Array of functions for boosting the query
     *
     * @var \KnihovnyCz\Geo\Parser
     */
    protected $parser;

    /**
     * Date converter
     *
     * @var \KnihovnyCz\Date\Converter
     */
    protected ?\KnihovnyCz\Date\Converter $dateConverter;

    /**
     * Array of functions for boosting the query
     *
     * @var array
     */
    protected array $boostFunctions = [];

    /**
     * Array of array parameters
     *
     * @var array
     */
    protected array $jsonFacets = [];

    /**
     * Array of facets
     *
     * @var array
     */
    protected array $nestedFacets = [];

    /**
     * Parent count
     *
     * @var bool
     */
    protected bool $parentCount = false;

    /**
     * Child filter
     *
     * @var bool
     */
    protected ?string $childFilter = null;

    /**
     * Deduplication type
     *
     * @var string
     */
    protected ?string $deduplicationType = null;

    /**
     * Nested filters
     *
     * @var array
     */
    protected array $nestedFilters = [];

    /**
     * Enable for all facets
     *
     * @var bool
     */
    private bool $enabledForAllFacets = false;

    /**
     * Facet method to use
     *
     * @var string
     */
    private string $facetMethod;

    /**
     * List of facets with zero count
     *
     * @var array
     */
    private array $zeroCountFacets = [];

    /**
     * All fields are ORed
     *
     * @var bool
     */
    private bool $allFacetsAreOr = false;

    /**
     * List of fields that are ORed
     *
     * @var array
     */
    private array $orFields = [];

    /**
     * Constructor
     *
     * @param \VuFind\Search\Base\Options  $options      Options to use
     * @param \VuFind\Config\PluginManager $configLoader Config loader
     * @param HierarchicalFacetHelper      $facetHelper  Hierarchical facet helper
     * @param \KnihovnyCz\Geo\Parser       $parser       Geo parser
     * @param \KnihovnyCz\Date\Converter   $converter    Date converter
     */
    public function __construct(
        $options,
        \VuFind\Config\PluginManager $configLoader,
        HierarchicalFacetHelper $facetHelper = null,
        \KnihovnyCz\Geo\Parser $parser = null,
        \KnihovnyCz\Date\Converter $converter = null
    ) {
        parent::__construct($options, $configLoader, $facetHelper);
        $this->parser = $parser;
        $this->dateConverter = $converter;
        $facetConfig = $configLoader->get($options->getFacetsIni());
        if (($specialFacets = $facetConfig->SpecialFacets) !== null) {
            $this->nestedFacets = isset($specialFacets->nested) ? $specialFacets
                ->nested->toArray() : [];
            $this->parentCount = $specialFacets->nestedParentCount ?? false;
        }
        $this->enabledForAllFacets = $facetConfig->JSON_API->enabled ?? false;
        $this->facetMethod = $facetConfig->JSON_API->method ?? 'smart';
        $searchConfig = $configLoader->get($options->getSearchIni());
        $this->deduplicationType = $searchConfig->Records->deduplication_type ?? null;
        if (isset($searchConfig->ChildRecordFilters)) {
            $this->childFilter = implode(
                ' AND ',
                array_values($searchConfig->ChildRecordFilters->toArray())
            );
        }
        $this->orFields  = array_map('trim', explode(',', $facetConfig->Results_Settings->orFacets ?? ''));
        $this->allFacetsAreOr = (isset($this->orFields[0]) && $this->orFields[0] == '*');
    }

    /**
     * Create search backend parameters for advanced features.
     *
     * @return ParamBag
     */
    public function getBackendParameters()
    {
        $backendParams = parent::getBackendParameters();
        foreach ($this->boostFunctions as $func) {
            $backendParams->add('boost', $func);
        }
        $remaining = [];
        $hasChildDocFilter = $this->deduplicationType == 'multiplying';
        $facetFields = $backendParams->get('facet.field') ?? [];
        $jsonFacetData = [];
        foreach ($facetFields as $facetField) {
            [$field, ] = DeduplicationHelper::parseField($facetField);
            $type = 'default';
            $nested = in_array($field, $this->nestedFacets);
            if (!$hasChildDocFilter && $nested) {
                $type = 'nested';
            }
            if ($hasChildDocFilter && !$nested) {
                $type = 'parent';
            }
            if ($type != 'default' || $this->enabledForAllFacets) {
                $jsonFacetData[$field] = $this->getJsonFacetConfig(
                    $field,
                    $backendParams,
                    $type
                );
            } else {
                $remaining[] = $facetField;
            }
        }
        if (empty($remaining)) {
            $backendParams->remove('facet.field');
        } else {
            $backendParams->set('facet.field', $remaining);
        }
        foreach ($this->jsonFacets as $field => $data) {
            $jsonFacetData[$field] = $data;
        }
        if (!empty($jsonFacetData)) {
            $backendParams->add('json.facet', json_encode($jsonFacetData));
        }
        return $backendParams;
    }

    /**
     * Add boost function to Solr query
     *
     * @param string $function boost function
     *
     * @return void
     */
    public function addBoostFunction($function)
    {
        $this->boostFunctions[] = $function;
    }

    /**
     * Add json facet to Solr query
     *
     * @param string $field      field to facet on
     * @param array  $parameters parameters
     *
     * @return void
     */
    public function addJsonFacet($field, $parameters)
    {
        $this->jsonFacets[$field] = $parameters;
    }

    /**
     * Return the current filters as an array of strings ['field:filter']
     *
     * @return array $filterQuery
     */
    public function getFilterSettings()
    {
        // Define Filter Query
        $filterQuery = [];
        $orFilters = [];
        $filterList = array_merge_recursive(
            $this->getHiddenFilters(),
            $this->filterList
        );
        $nestedFilters = [];
        foreach ($filterList as $field => $filter) {
            if ($orFacet = str_starts_with($field, '~')) {
                $field = substr($field, 1);
            }
            if ($neg = str_starts_with($field, '-')) {
                $field = substr($field, 1);
            }
            foreach ($filter as $value) {
                $fq = new FilterQuery();
                // Special case -- complex filter, that should be taken as-is:
                if ($field == '#') {
                    $fq->setRawQuery($value);
                } else {
                    $fq->setField($field);
                    $fq->setQuery($value);
                }
                $fq->setNegation($neg);
                if ($orFacet) {
                    $orFilters[$field] ??= [];
                    $fq->setField(null);
                    $orFilters[$field][] = $fq->getFilter();
                } else {
                    $this->configureFilter($fq);
                    $filterQuery[] = $fq->getFilter();
                    if ($fq->isParentQueryParser()) {
                        $copy = clone $fq;
                        $copy->setParentQueryParser(false);
                        $copy->setTag(null);
                        $nestedFilters[] = $copy->getFilter();
                    }
                }
            }
        }
        foreach ($orFilters as $field => $parts) {
            $orFilter = new FilterQuery();
            $orFilter->setTag($field . '_filter');
            $orFilter->setField($field);
            $orFilter->setRawQuery('(' . implode(' OR ', $parts) . ')');
            $this->configureFilter($orFilter);
            $filterQuery[] = $orFilter->getFilter();
            if ($orFilter->isParentQueryParser()) {
                $copy = clone $orFilter;
                $copy->setParentQueryParser(false);
                $copy->setTag(null);
                $nestedFilters[] = $copy->getFilter();
            }
        }
        if (count($nestedFilters) > 1) {
            $nestedFilter = new FilterQuery();
            $nestedFilter->setParentQueryParser(true);
            $nestedFilter->setChildQuery($this->childFilter);
            $nestedFilter->setTag('nested_facet_filter');
            $nestedFilter->setRawQuery('(' . implode(' AND ', $nestedFilters) . ')');
            $filterQuery[] = $nestedFilter->getFilter();
        }
        return $filterQuery;
    }

    /**
     * Get configuration for nested facet
     *
     * @param string                 $facetField field
     * @param \VuFindSearch\ParamBag $params     parameters
     * @param string                 $type       default, parent or nested
     *
     * @return array
     */
    protected function getJsonFacetConfig($facetField, $params, $type)
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
        $excludeTags = [];
        if ($this->isOrFacet($facetField)) {
            $excludeTags[] =  $facetField . '_filter';
        }
        if ($type != 'default') {
            $excludeTags[] = 'nested_facet_filter';
        }
        $domain = [];
        if (!empty($excludeTags)) {
            $domain['excludeTags'] = $excludeTags;
        }
        if ($type == 'default') {
            return $facetConfig;
        }

        $q = null;
        $nestedFilter = null;

        $appliedFilters = [];
        foreach ($this->nestedFilters as $filter) {
            if (!$this->isOrFacet($filter->getField()) || $facetField != $filter->getField()) {
                $appliedFilters[] = $this->putBrackets($filter->getFilter());
            }
        }
        if (!empty($appliedFilters)) {
            $nestedFilter = implode(' AND ', $appliedFilters);
        }
        if ($type == 'nested') {
            $domain['blockChildren'] = DeduplicationHelper::PARENT_FILTER;
            $q = DeduplicationHelper::CHILD_FILTER;
            if ($nestedFilter != null) {
                $q .= ' AND ' . $nestedFilter;
            }
            if ($this->childFilter != null) {
                $q .= ' AND (' . $this->childFilter . ')';
            }
            if ($this->parentCount) {
                $facetConfig['facet'] = [ 'real_count' => 'uniqueBlock(_root_)' ];
            }
        } elseif ($type == 'parent') {
            $domain['blockParent'] = DeduplicationHelper::PARENT_FILTER;
            $queryDomain = [
                'blockChildren' => DeduplicationHelper::PARENT_FILTER,
            ];
            $queryDomainFilter = '({!lucene v=$childrenQuery})';
            if ($nestedFilter != null) {
                $queryDomainFilter .= ' AND ' . $nestedFilter;
            }
            if ($this->childFilter != null) {
                $queryDomainFilter .= ' AND (' . $this->childFilter . ')';
            }
            $queryDomain['filter'] = $queryDomainFilter;
            $facetConfig['facet'] = [
                'count' => [
                    'type' => 'query',
                    'domain' => $queryDomain,
                ],
            ];
        }
        return [
            'type'   => 'query',
            'q'      => $q,
            'domain' => $domain,
            'facet'  => [
                $facetField => $facetConfig,
            ],
        ];
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
            "f.{$field}.facet.{$parameter}",
            "facet.{$parameter}",
        ];
        foreach ($keys as $key) {
            if ($params->hasParam($key)) {
                return $params->get($key)[0];
            }
        }
        return $default;
    }

    /**
     * Configure filter
     *
     * @param FilterQuery $filter filter
     *
     * @return FilterQuery
     */
    protected function configureFilter(FilterQuery $filter): FilterQuery
    {
        $nested = in_array($filter->getField(), $this->nestedFacets);
        if ($nested && $this->deduplicationType == 'multiplying') {
            $this->nestedFilters[] = clone $filter;
            return $filter;
        } elseif ($nested) {
            $this->nestedFilters[] = clone $filter;
            $filter->setParentQueryParser(true);
            $filter->setChildQuery($this->childFilter);
        } elseif ($this->deduplicationType == 'multiplying') {
            $filter->setChildrenQueryParser(true);
        }
        return $filter;
    }

    /**
     * Return if the field is ORed
     *
     * @param string $field field name
     *
     * @return bool  is OR facet
     */
    protected function isOrFacet(string $field): bool
    {
        return $this->allFacetsAreOr || in_array($field, $this->orFields);
    }

    /**
     * Put brackets around query if not already bracketed
     *
     * @param string $query query
     *
     * @return string bracketed query
     */
    protected function putBrackets($query)
    {
        if (str_starts_with($query, '(') || str_starts_with($query, '-(')) {
            return $query;
        }
        return '(' . $query . ')';
    }

    /**
     * Format a single filter for use in getFilterList().
     *
     * @param string $field     Field name
     * @param string $value     Field value
     * @param string $operator  Operator (AND/OR/NOT)
     * @param bool   $translate Should we translate the label?
     *
     * @return array
     */
    protected function formatFilterListEntry($field, $value, $operator, $translate)
    {
        $displayText = null;
        if ($field == 'scale_int_facet_mv') {
            $range = $this->parser->parseRangeQuery($value);
            if ($range != null) {
                [$min, $max] = $range;
                $to = $this->translate('map_scale_to');
                $displayText = "1:$min $to 1:$max";
            }
        } elseif ($field == 'long_lat') {
            $displayText = $this->parser->parseBoundingBoxForDisplay($value)
                ?? $value;
        } elseif (str_ends_with($field, '_date')) {
            $displayText = $this->displayDateRange($value);
        } elseif (in_array($field, self::TREAT_AS_NON_HIERARCHICAL)) {
            $rawDisplayText = $this->getFacetValueRawDisplayText($field, $value);
            $displayText = $translate
                ? $this->translateFacetValue($field, $rawDisplayText)
                : $rawDisplayText;
        }
        if ($displayText != null) {
            return compact('value', 'displayText', 'field', 'operator');
        }
        return parent::formatFilterListEntry($field, $value, $operator, $translate);
    }

    /**
     * Parse data range for display format
     *
     * @param string $value value
     *
     * @return string
     */
    protected function displayDateRange($value)
    {
        if (preg_match('/\\[([^ ]+) TO ([^ ]+)\\]/', $value, $match)) {
            $from = $this->parseSolrDate($match[1]);
            $to = $this->parseSolrDate($match[2]);
            return "$from - $to";
        }
        return $value;
    }

    /**
     * Parse solr date
     *
     * @param string $value value
     *
     * @return \DateTime|false
     */
    protected function parseSolrDate($value)
    {
        if ($value == '*') {
            return $value;
        }
        try {
            return $this->dateConverter->convertToDisplayDateFromSolr($value);
        } catch (\VuFind\Date\DateException $ex) {
            return $value;
        }
    }
}
