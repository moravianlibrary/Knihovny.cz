<?php

/**
 * Model for missing records -- used for saved favorites that have been deleted
 * from the index.
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
 * @category VuFind
 * @package  RecordDrivers
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

declare(strict_types=1);

namespace KnihovnyCz\RecordDriver;

use VuFind\RecordDriver\Missing as Base;

/**
 * Model for missing records -- used for saved favorites that have been deleted
 * from the index.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class Missing extends Base
{
    /**
     * Get the main author of the record.
     *
     * @return array
     */
    public function getPrimaryAuthors(): array
    {
        $ilsDetails = $this->getExtraDetail('ils_details');
        if (isset($ilsDetails['author']) && !empty($ilsDetails['author'])) {
            return [ $ilsDetails['author'] ];
        } elseif (
            isset($ilsDetails['authors'])
            && is_array($ilsDetails['authors'])
        ) {
            return $ilsDetails['authors'];
        }
        return [];
    }

    /**
     * Get an array of all the formats associated with the record.
     *
     * @return array
     */
    public function getFormats(): array
    {
        $ilsDetails = $this->getExtraDetail('ils_details');
        if (isset($ilsDetails['format'])) {
            return [ $ilsDetails['format'] ];
        }
        return ['Unknown'];
    }
}
