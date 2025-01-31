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

    /**
     * Get the current search parameters as a GET query.
     *
     * @param bool $escape Should we escape the string for use in the view?
     * @param bool $number Number duplicated parameters
     *
     * @return string
     */
    public function getParams($escape = true, bool $number = false)
    {
        return '?' . static::buildQueryString($this->urlParams, $escape, $number);
    }

    /**
     * Turn an array into a properly URL-encoded query string. This is
     * equivalent to the built-in PHP http_build_query function, but it handles
     * arrays in a more compact way and ensures that ampersands don't get
     * messed up based on server-specific settings.
     *
     * @param array $a      Array of parameters to turn into a GET string
     * @param bool  $escape Should we escape the string for use in the view?
     * @param bool  $number Number duplicated parameters
     *
     * @return string
     */
    public static function buildQueryString($a, $escape = true, bool $number = false)
    {
        $parts = [];
        foreach ($a as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $index => $current) {
                    $ord = ($number && !str_starts_with($key, 'lookfor')) ? $index : '';
                    $parts[] = urlencode($key . '[' . $ord . ']') . '=' . urlencode($current ?? '');
                }
            } else {
                $parts[] = urlencode($key) . '=' . urlencode($value ?? '');
            }
        }
        $retVal = implode('&', $parts);
        return $escape ? htmlspecialchars($retVal) : $retVal;
    }
}
