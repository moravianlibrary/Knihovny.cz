<?php

/**
 * Solr deduplication listener for views.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace KnihovnyCz\Search\Solr;

use function in_array;

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
