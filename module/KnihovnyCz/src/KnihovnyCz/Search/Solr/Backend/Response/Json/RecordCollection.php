<?php

declare(strict_types=1);

namespace KnihovnyCz\Search\Solr\Backend\Response\Json;

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
    public function getFacets(): array
    {
        if (null === $this->facetFields) {
            $this->facetFields = $this->parseFacets();
        }
        return $this->facetFields;
    }

    /**
     * Parse facets from response
     *
     * @return array
     */
    protected function parseFacets()
    {
        $facets = parent::getFacets();
        if (!isset($this->response['facets'])) {
            return $facets;
        }
        $foundResults = (int)$this->response['response']['numFound'];
        foreach ($this->response['facets'] as $field => $facet) {
            if (is_array($facet)) {
                $buckets = [];
                if (isset($facet['buckets'])) {
                    $buckets = $facet['buckets'];
                } elseif (isset($facet[$field]['buckets'])) {
                    $buckets = $facet[$field]['buckets'];
                }
                $results = [];
                foreach ($buckets as $bucket) {
                    $count = $bucket['real_count'] ?? $bucket['count'];
                    $count = (int)(is_array($count) ? $count['count'] : $count);
                    $results[$bucket['val']] = min($count, $foundResults);
                }
                arsort($results);
                $facets[$field] = $results;
            }
        }
        return $facets;
    }
}
