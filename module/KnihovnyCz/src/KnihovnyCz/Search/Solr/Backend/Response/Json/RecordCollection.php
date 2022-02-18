<?php

/**
 * Simple JSON-based record collection.
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
 * @link     https://vufind.org
 */
namespace KnihovnyCz\Search\Solr\Backend\Response\Json;

use VuFindSearch\Backend\Solr\Response\Json\Facets;
use VuFindSearch\Backend\Solr\Response\Json\RecordCollection as Base;

/**
 * Simple JSON-based record collection.
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class RecordCollection extends Base
{
    /**
     * Return SOLR facet information.
     *
     * @return array
     */
    public function getFacets()
    {
        if (!$this->facets) {
            $this->facets = new Facets($this->parseFacets());
        }
        return $this->facets;
    }

    /**
     * Parse facets from response
     *
     * @return array
     */
    protected function parseFacets()
    {
        $facets = $this->response['facet_counts'];
        if (isset($this->response['facets'])) {
            foreach ($this->response['facets'] as $field => $facet) {
                if (is_array($facet)) {
                    $buckets = [];
                    if (isset($facet['buckets'])) {
                        $buckets = $facet['buckets'];
                    } elseif (isset($facet[$field]['buckets'])) {
                        $buckets = $facet[$field]['buckets'];
                        // sort JSON facets by count
                        usort(
                            $buckets,
                            function ($a, $b) {
                                return $b['count'] <=> $a['count'];
                            }
                        );
                    }
                    foreach ($buckets as $bucket) {
                        $value = $bucket['val'];
                        $count = $bucket['count'];
                        $facets['facet_fields'][$field][] = [$value, $count];
                    }
                }
            }
        }
        return $facets;
    }
}
