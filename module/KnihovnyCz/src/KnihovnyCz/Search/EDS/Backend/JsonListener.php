<?php

/**
 * EBSCO EDS API JSON parser
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
 * @category EBSCO
 * @package  EBSCO
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */

namespace KnihovnyCz\Search\EDS\Backend;

/**
 * EBSCO EDS API JSON parser
 *
 * @category EBSCO
 * @package  EBSCO
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class JsonListener extends \JsonStreamingParser\Listener\InMemoryListener
{
    protected const MAX_LENGTH = 65536;

    protected $longFields = [ 'Value' ];

    /**
     * Process value
     *
     * @param mixed $value value
     *
     * @return void
     */
    public function value($value): void
    {
        $key = end($this->keys);
        if (
            is_string($value) && strlen($value) > self::MAX_LENGTH
            && in_array($key, $this->longFields)
        ) {
            $value = substr($value, 0, self::MAX_LENGTH);
        }
        $this->insertValue($value);
    }
}
