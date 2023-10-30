<?php

namespace KnihovnyCz\Search\Solr;

/**
 * Solr deduplication helper
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class DeduplicationHelper
{
    public const CHILD_FILTER = 'merged_child_boolean:true';

    public const PARENT_FILTER = 'merged_boolean:true';

    protected const SOLR_LOCAL_PARAMS = "/(\\{[^\\}]*\\})*(\S+)/";

    /**
     * Check search parameters for child records filter
     *
     * @param \VuFindSearch\ParamBag $params Search parameters
     *
     * @return bool
     */
    public static function hasChildFilter($params)
    {
        $filters = $params->get('fq') ?? [];
        return in_array(self::CHILD_FILTER, $filters);
    }

    /**
     * Parse field and local parameters from faceting parameter
     *
     * @param $facet facet field
     *
     * @return array field name and local parameters
     */
    public static function parseField($facet)
    {
        $field = $facet;
        $localParams = null;
        $matches = [];
        if (preg_match(self::SOLR_LOCAL_PARAMS, $field, $matches)) {
            $localParams = $matches[1];
            $field = $matches[2];
        }
        return [$field, $localParams];
    }
}
