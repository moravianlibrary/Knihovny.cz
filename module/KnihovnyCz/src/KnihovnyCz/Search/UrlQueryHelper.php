<?php

namespace KnihovnyCz\Search;

/**
 * VuFind Search Runner
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class UrlQueryHelper extends \VuFind\Search\UrlQueryHelper
{
    /**
     * Remove all filters.
     *
     * @param string $field field to remove from filters
     *
     * @return UrlQueryHelper
     */
    public function removeFilterByField($field)
    {
        $fields = [ $field, '~' . $field];
        $params = $this->urlParams;
        $newFilters = [];
        $filters = $params['filter'] ?? [];
        foreach ($filters as $filter) {
            [$currentField, $currentValue] = $this->parseFilter($filter);
            if (!in_array($currentField, $fields)) {
                $newFilters[] = $filter;
            }
        }
        $params['filter'] = $newFilters;
        return new static($params, $this->queryObject, $this->config, false);
    }
}
