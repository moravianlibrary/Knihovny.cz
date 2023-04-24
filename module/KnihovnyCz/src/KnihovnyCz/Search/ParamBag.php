<?php

/**
 * Parameter bag.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace KnihovnyCz\Search;

/**
 * Lightweight wrapper for request parameters.
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class ParamBag extends \VuFindSearch\ParamBag
{
    /**
     * Is child filter enabled?
     *
     * @var bool
     */
    protected $applyChildFilter = true;

    /**
     * Set appply child filter
     *
     * @param bool $applyChildFilter apply child filter
     *
     * @return void
     */
    public function setApplyChildFilter(bool $applyChildFilter)
    {
        $this->applyChildFilter = $applyChildFilter;
    }

    /**
     * Is child filter enabled?
     *
     * @return bool
     */
    public function isApplyChildFilter()
    {
        return $this->applyChildFilter;
    }
}
