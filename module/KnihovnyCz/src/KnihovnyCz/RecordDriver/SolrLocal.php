<?php
/**
 * Knihovny.cz solr marc local record driver
 *
 * PHP version 7
 *
 * Copyright (C) The Moravian Library 2015-2019.
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
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
namespace KnihovnyCz\RecordDriver;

/**
 * Knihovny.cz solr marc local record driver
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class SolrLocal extends \KnihovnyCz\RecordDriver\SolrMarc
{
    use Feature\CitaceProTrait;

    /**
     * Get the sigla for display
     *
     * @return string
     */
    public function getSiglaDisplay()
    {
        return $this->fields['sigla_display'] ?? null;
    }

    /**
     * Get an array of information about record holdings, obtained in real-time
     * from the ILS.
     *
     * @return array
     */
    public function getRealTimeHoldings()
    {
        $holdings = [];
        try {
            $holdings = parent::getRealTimeHoldings();
        } catch (\VuFind\Exception\ILS $exception) {
            $holdings = $this->getOfflineHoldings();
        }
        return $holdings;
    }

    /**
     * Get an array of information about record holdings, obtained in real-time
     * from the ILS.
     *
     * @return array
     */
    public function getOfflineHoldings()
    {
        $items = [];
        $f996 = $this->fields['mappings996_display_mv'] ?? [];
        foreach ($f996 as $line) {
            [
                $itemId, $callnumber, $location, $callnumber_second,
                $description, $notes, $year, $volume, $issue, $status,
                $collection_desc, $agency_id, $sequenceNo, $copy_number
            ] = str_getcsv($line);
            $item_id = $agency_id . $itemId . $sequenceNo;
            $items[] = compact(
                'item_id', 'callnumber', 'location', 'callnumber_second',
                'description', 'notes', 'year', 'volume', 'issue', 'status',
                'collection_desc', 'agency_id', 'copy_number'
            );
        }
        return empty($items) ? [] :
            [
                'holdings' => [
                    [
                        'location' => 'default',
                        'items' => $items
                    ]
                ],
            ];
    }

    /**
     * Does record has any items attached?
     *
     * @return bool
     */
    public function hasOfflineHoldings()
    {
        return !empty($this->fields['mappings996_display_mv']);
    }
}
