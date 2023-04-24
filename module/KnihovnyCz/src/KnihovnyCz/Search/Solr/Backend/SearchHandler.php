<?php

/**
 * VuFind SearchHandler.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */

namespace KnihovnyCz\Search\Solr\Backend;

use KnihovnyCz\Search\Solr\Backend\SearchQueryParameters as Parameters;

/**
 * VuFind SearchHandler.
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class SearchHandler extends \VuFindSearch\Backend\Solr\SearchHandler
{
    /**
     * Parent query parameters
     *
     * @var Parameters
     */
    protected $externalQueryParameters = null;

    /**
     * Constructor.
     *
     * @param array      $spec                 Search handler specification
     * @param string     $defaultDismaxHandler Default dismax handler (if no
     * DismaxHandler set in specs).
     * @param Parameters $parameters           Search query parameters
     *
     * @return void
     */
    public function __construct(
        array $spec,
        $defaultDismaxHandler = 'dismax',
        $parameters = null
    ) {
        parent::__construct($spec, $defaultDismaxHandler);
        $this->specs['ChildrenQuery'] = $spec['ChildrenQuery'] ?? false;
        $this->externalQueryParameters = $parameters;
    }

    /**
     * Return query string for specified search string.
     *
     * If optional argument $advanced is true the search string contains
     * advanced lucene query syntax.
     *
     * @param string $search   Search string
     * @param bool   $advanced Is the search an advanced search string?
     *
     * @return string
     */
    protected function createQueryString($search, $advanced = false)
    {
        $query = parent::createQueryString($search, $advanced);
        if ($this->isApplyParentFilter()) {
            $query = "(merged_boolean:true AND " . $query . ")";
            $key = $this->externalQueryParameters->add($query);
            $query = "{!child of='merged_boolean:true' v=\$$key}";
        }
        return $query;
    }

    /**
     * Switch query to parent?
     *
     * @return boolean
     */
    protected function isApplyParentFilter()
    {
        $childSearch = $this->specs['ChildrenQuery'] ?? false;
        return !$childSearch &&
            $this->externalQueryParameters->isSwitchToParentQuery();
    }
}
