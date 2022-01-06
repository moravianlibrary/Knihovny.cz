<?php
/**
 * Date/time conversion functionality.
 *
 * PHP version 7
 *
 * Copyright (C) Moravian library 2021.
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
 * @package  Date
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace KnihovnyCz\Date;

use DateTime;
use VuFind\Date\DateException;

/**
 * Date/time conversion functionality.
 *
 * @category VuFind
 * @package  Date
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Converter extends \VuFind\Date\Converter
{
    /**
     * Public method for conversion of an admin defined date string
     * to a PHP DateTime
     *
     * @param string $displayDate The display formatted date string
     *
     * @throws DateException
     * @return DateTime|false     Parsed date
     */
    public function parseDisplayDate($displayDate)
    {
        return DateTime::createFromFormat(
            $this->displayDateFormat, $displayDate, $this->timezone
        );
    }
}
