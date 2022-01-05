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
        $filters = [
            'year' => [],
            'volume' => [],
        ];
        $f996 = $this->fields['mappings996_display_mv'] ?? [];
        $isCaslin = str_starts_with($this->getUniqueID(), 'caslin');
        $isAleph = $this->ils->getDriverName($this->getUniqueID()) === 'Aleph';
        foreach ($f996 as $line) {
            [
                $itemId, $callnumber, $location, $callnumber_second,
                $description, $notes, $year, $volume, $issue, $status,
                $collection_desc, $agency_id, $sequenceNo, $copy_number,
                $catalog_link
            ] = str_getcsv($line);
            $item_id = ($isAleph ? $agency_id : '') . $itemId . $sequenceNo;
            if ($isCaslin) {
                $location = $this->translateWithPrefix('Sigla::', $location);
            }
            $items[] = compact(
                'item_id',
                'callnumber',
                'location',
                'callnumber_second',
                'description',
                'notes',
                'year',
                'volume',
                'issue',
                'status',
                'collection_desc',
                'agency_id',
                'copy_number',
                'catalog_link'
            );
            $filters['year'][$year] = $this->extractYear($year);
            $filters['volume'][$volume] = $this->extractVolume($volume);
        }
        foreach ($filters as $key => &$values) {
            if (count($values) > 1) {
                $reverse = ($key == 'year') ? 1 : -1;
                uasort(
                    $values,
                    function ($a, $b) use ($reverse) {
                        if (is_int($a) && is_int($b)) {
                            return $reverse * ($b <=> $a);
                        } elseif (is_int($a)) {
                            return -1;
                        } elseif (is_int($b)) {
                            return 1;
                        }
                        return strcmp($a, $b);
                    }
                );
            } else {
                $values = [];
            }
        }
        return empty($items) ? [] :
            [
                'holdings' => [
                    [
                        'location' => 'default',
                        'items' => $items
                    ]
                ],
                'filters' => $filters,
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

    /**
     * Try to extract year from 996|y in holdings
     *
     * @param string $year year
     *
     * @return string|int  year or original value as string if extraction fails
     */
    protected function extractYear($year)
    {
        $matches = [];
        if (preg_match('/([0-9]{4})/', $year, $matches) == 1) {
            return intval($matches[1]);
        }
        return $year;
    }

    /**
     * Try to extract volume from 996|v in hodings
     *
     * @param string $volume volume
     *
     * @return string|int    volume or original value as string if extraction
     * fails
     */
    protected function extractVolume($volume)
    {
        $matches = [];
        if (preg_match('/([0-9]+)/', $volume, $matches) == 1) {
            return intval($matches[1]);
        }
        return $volume;
    }
}
