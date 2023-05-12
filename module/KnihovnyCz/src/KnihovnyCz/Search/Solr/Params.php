<?php

/**
 * Solr aspect of the Search Multi-class (Params)
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
 * @category KnihovnyCz
 * @package  Search_Solr
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

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
    public const SOLR_DATE_FORMAT = 'Y-m-d\TH:i:s.z\Z';

    protected const TREAT_AS_NON_HIERARCHICAL = [
        'region_institution_facet_mv',
        'local_region_institution_facet_mv'
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
    protected $dateConverter;

    /**
     * Array of functions for boosting the query
     *
     * @var array
     */
    protected $boostFunctions = [];

    /**
     * Array of array parameters
     *
     * @var array
     */
    protected $jsonFacets = [];

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
        $jsonFacet = [];
        foreach ($this->jsonFacets as $field => $parameters) {
            $jsonFacet[$field] = $parameters;
        }
        if (!empty($jsonFacet)) {
            $backendParams->add('json.facet', json_encode($jsonFacet));
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
