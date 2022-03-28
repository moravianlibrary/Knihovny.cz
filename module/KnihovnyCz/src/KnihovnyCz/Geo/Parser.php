<?php
/**
 * Class Parser
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
 * @package  KnihovnyCz\Geography
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Geo;

/**
 * Class Parser
 *
 * @category VuFind
 * @package  KnihovnyCz\Geography
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Parser
{
    /**
     * Precision for rounding latitude and longitude
     *
     * @var int
     */
    protected const COORDINATE_PRECISION = 3;

    /**
     * Pattern for parsing map scale range filter
     *
     * @var string
     */
    protected const RANGE_PATTERN = '/\\[([0-9]+) TO ([0-9]+)\\]/';

    /**
     * Pattern for parsing bounding box filter
     *
     * @var string
     */
    protected const BBOX_PATTERN = '/Intersects\(ENVELOPE\(([0-9 \\-\\.,]+)\)\)/';

    /**
     * Parse range query, normalize it and return min and max value.
     *
     * @param string $filter filter
     *
     * @return null|array array with min and max value or null for
     * invalid range filter
     */
    public function parseRangeQuery($filter)
    {
        $match = [];
        if (preg_match(self::RANGE_PATTERN, $filter, $match)) {
            return $this->normalizeRange($match[1], $match[2]);
        }
        return null;
    }

    /**
     * Normalize range - return min and max value from range.
     *
     * @param string $from from
     * @param string $to   to
     *
     * @return null|string normalized range, array with two elemetns - minimal
     * and maximal value or null if invalid
     */
    public function normalizeRange($from, $to)
    {
        if ($from == null || $to == null
            || !is_numeric($from) || !is_numeric($to)
        ) {
            return null;
        }
        $from = (int)$from;
        $to = (int)$to;
        $min = min($from, $to);
        $max = max($from, $to);
        return [ $min, $max ];
    }

    /**
     * Parse bounding box and return user friendly representation.
     *
     * @param string $filter filter to parser
     *
     * @return string|null  value to display or null for invalid filter
     */
    public function parseBoundingBoxForDisplay($filter)
    {
        $points = $this->parseBoundingBox($filter);
        if ($points != null) {
            return $this->getBoundingBoxForDisplay($points);
        }
        return null;
    }

    /**
     * Return user friendly representation of bounding box
     *
     * @param $points points
     *
     * @return string
     */
    protected function getBoundingBoxForDisplay($points)
    {
        $p1 = $this->getLatitudeDisplayText($points[2]);
        $p2 = $this->getLongitudeDisplayText($points[0]);
        $p3 = $this->getLatitudeDisplayText($points[3]);
        $p4 = $this->getLongitudeDisplayText($points[1]);
        return "$p1, $p2 - $p3, $p4";
    }

    /**
     * Parse bounding box from filter and return as points
     *
     * @param string $filter filter
     *
     * @return null|array points
     */
    protected function parseBoundingBox($filter)
    {
        $match = [];
        if (preg_match(self::BBOX_PATTERN, $filter, $match)) {
            $value = $match[1];
            return array_map(
                function ($val) {
                    return floatval(trim($val));
                },
                explode(',', $value)
            );
        }
        return null;
    }

    /**
     * Return user friendly representation of latitude
     *
     * @param float $latitude latitude
     *
     * @return string formatted latitude
     */
    protected function getLatitudeDisplayText($latitude)
    {
        $latitude = round($latitude, self::COORDINATE_PRECISION);
        return ($latitude > 0) ? $latitude . 'N' : abs($latitude) . 'S';
    }

    /**
     * Return user friendly representation of longitude
     *
     * @param float $longitude point
     *
     * @return string formatted longitude
     */
    protected function getLongitudeDisplayText($longitude)
    {
        $longitude = round($longitude, self::COORDINATE_PRECISION);
        return ($longitude > 0) ? $longitude . 'E' : abs($longitude) . 'W';
    }
}
