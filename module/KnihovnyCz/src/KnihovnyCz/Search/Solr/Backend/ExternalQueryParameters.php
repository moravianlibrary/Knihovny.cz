<?php

/**
 * External search query parameters
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

/**
 * External search query parameters
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class ExternalQueryParameters
{
    protected const PREFIX = 'query';

    protected $index = 0;

    protected $parameters = [];

    protected $switchToParentQuery = false;

    /**
     * Add search query to parameters and return parameter name for it
     *
     * @param string $search search
     *
     * @return string parameter name
     */
    public function add($search)
    {
        $key = self::PREFIX . $this->index++;
        $this->parameters[$key] = $search;
        return $key;
    }

    /**
     * Set switch to parent query
     *
     * @param bool $switchToParentQuery switch to parent query
     *
     * @return void
     */
    public function setSwitchToParentQuery(bool $switchToParentQuery)
    {
        $this->switchToParentQuery = $switchToParentQuery;
    }

    /**
     * Is set to switch parent query
     *
     * @return bool
     */
    public function isSwitchToParentQuery(): bool
    {
        return $this->switchToParentQuery;
    }

    /**
     * Return parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Reset this object for new query
     *
     * @return void
     */
    public function reset()
    {
        $this->index = 0;
        $this->parameters = [];
        $this->switchToParentQuery = false;
    }
}
