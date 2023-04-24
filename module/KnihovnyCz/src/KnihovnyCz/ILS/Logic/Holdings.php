<?php

/**
 * Class Holdings
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\ILS\Logic
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\ILS\Logic;

/**
 * Class Holdings
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ILS\Logic
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Holdings
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_NOT_AVAILABLE = 'unavailable';

    public const STATUS_TEMPORARY_NOT_AVAILABLE = 'temporary-unavailable';

    public const STATUS_UNKNOWN = 'unknown';

    public const STATUS_UNDECIDABLE = 'cant-decide';

    /**
     * Availability status mapping
     *
     * @var array|string[]
     */
    protected array $availability = [
        'Available On Shelf' => self::STATUS_AVAILABLE, // XCNCIP2
        'Available on Shelf' => self::STATUS_AVAILABLE, // XCNCIP2
        'On Shelf' => self::STATUS_AVAILABLE, // Koha
        'Available For Pickup' => self::STATUS_TEMPORARY_NOT_AVAILABLE, // XCNCIP2
        'Available for Pickup' => self::STATUS_TEMPORARY_NOT_AVAILABLE, // XCNCIP2
        'On Loan' => self::STATUS_TEMPORARY_NOT_AVAILABLE, // XCNCIP2, Aleph
        'Charged' => self::STATUS_TEMPORARY_NOT_AVAILABLE, // Koha
        'Overdue' => self::STATUS_TEMPORARY_NOT_AVAILABLE, // Koha
        'On Order' => self::STATUS_TEMPORARY_NOT_AVAILABLE, // XCNCIP2, Aleph
        'On Hold' => self::STATUS_TEMPORARY_NOT_AVAILABLE, // Koha
        'On Holdshelf' => self::STATUS_TEMPORARY_NOT_AVAILABLE, // Koha
        'In Process' => self::STATUS_TEMPORARY_NOT_AVAILABLE, // XCNCIP2
        'In Transit Between Library Locations'
            => self::STATUS_TEMPORARY_NOT_AVAILABLE, // XCNCIP2
        'In Transit On Hold' => self::STATUS_TEMPORARY_NOT_AVAILABLE, // Koha
        'In Transit' => self::STATUS_TEMPORARY_NOT_AVAILABLE, // Koha
        'Circulation Status Undefined' => self::STATUS_UNKNOWN, // XCNCIP2
        'available' => self::STATUS_AVAILABLE, // Aleph
        'Not For Loan' => self::STATUS_NOT_AVAILABLE, //XCNCIP2
        'Not Available' => self::STATUS_NOT_AVAILABLE, //XCNCIP2
        'Withdrawn' => self::STATUS_NOT_AVAILABLE, // Koha
        'Lost' => self::STATUS_NOT_AVAILABLE, //Koha
        'No information available' => self::STATUS_UNDECIDABLE, // Koha
    ];

    /**
     * Get availability status
     *
     * @param string $status Status string from ILS Driver
     *
     * @return string One of this class STATUS_* constants
     */
    public function getAvailabilityByStatus(string $status): string
    {
        return $this->availability[$status] ?? self::STATUS_UNDECIDABLE;
    }
}
