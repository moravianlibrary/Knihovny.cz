<?php

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
     * Get the main author of the record for sorting purposes.
     *
     * @return string
     */
    public function getPrimaryAuthorForSorting()
    {
        $parent = $this->getParentRecord();
        if ($parent != null) {
            return $parent->tryMethod('getPrimaryAuthorForSorting');
        }
        return null;
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
        /* @phpstan-ignore-next-line */

        $isAleph = $this->isAleph();
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
            $year = trim($year);
            $volume = trim($volume);
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
                        'items' => $items,
                    ],
                ],
                'filters' => $filters,
            ];
    }

    /**
     * Get an array of offline information about holding by item id
     *
     * @param string $itemId item id to return
     *
     * @return array
     */
    public function getOfflineHoldingByItemId($itemId)
    {
        $holdings = $this->getOfflineHoldings();
        if (empty($holdings)) {
            return [];
        }
        foreach ($holdings['holdings'][0]['items'] as $item) {
            if ($item['item_id'] == $itemId) {
                return $item;
            }
        }
        return [];
    }

    /**
     * Get an array of offline information about holding by barcode
     *
     * @param string $barcode barcode to return
     *
     * @return array
     */
    public function getOfflineHoldingByBarcode($barcode)
    {
        $items = $this->getStructuredDataFieldArray('996');
        foreach ($items as $item) {
            if ($item['b'] == $barcode) {
                return $item;
            }
        }
        return [];
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
