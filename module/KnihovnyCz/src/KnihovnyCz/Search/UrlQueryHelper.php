<?php

/**
 * VuFind Search Runner
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
 * @link     https://vufind.org Main Site
 */

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
