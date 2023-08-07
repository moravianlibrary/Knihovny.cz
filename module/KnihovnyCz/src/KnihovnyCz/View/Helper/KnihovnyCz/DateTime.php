<?php

/**
 * View helper for formatting dates and times.
 *
 * PHP version 8
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
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use VuFind\View\Helper\Root\DateTime as Base;

/**
 * View helper for formatting dates and times
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class DateTime extends Base
{
    /**
     * Return display date format for JQuery
     *
     * @return string
     */
    public function getDisplayDateFormatForJquery(): string
    {
        $dueDateHelpString
            = $this->converter->convertToDisplayDate("m-d-y", "11-22-3333");
        $search = ["11", "22", "3333"];
        $replace = [
            $this->getView()->translate("mm"),
            $this->getView()->translate("dd"),
            $this->getView()->translate("yy"),
        ];
        return str_replace($search, $replace, $dueDateHelpString);
    }
}
