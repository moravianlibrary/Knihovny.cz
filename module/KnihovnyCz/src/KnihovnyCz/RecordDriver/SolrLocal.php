<?php

namespace KnihovnyCz\RecordDriver;

use VuFind\RecordDriver\Response\PublicationDetails;

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
        $recordId = $this->getUniqueID();
        $f996 = $this->fields['mappings996_display_mv'] ?? [];
        if (empty($f996)) {
            $itemLinks = $this->getItemLinks('UP');
            if (count($itemLinks) == 1) {
                $record = reset($itemLinks);
                if (($record = $record['record'] ?? null) != null) {
                    $recordId = $record->getUniqueID();
                    $f996 = $record->fields['mappings996_display_mv'] ?? [];
                }
            }
        }
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
            if (!preg_match('/^https?:\/\//', $catalog_link) && !empty($catalog_link)) {
                $catalog_link = 'https://' . $catalog_link;
            }
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
                'filters'  => $filters,
                'recordId' => $recordId,
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
     * Try to extract volume from 996|v in holdings
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

    /**
     * Get an array of publication detail lines from normalized MARC field 978.
     * Output has the same structure as getPublicationDetails
     *
     * @return array
     */
    protected function getNormalizedPublicationDetails(): array
    {
        $fields978 = $this->getMarcReader()->getFields('978', ['a', 'b', 'c']);
        $details = [];
        foreach ($fields978 as $field) {
            $data = [];
            foreach ($field['subfields'] ?? [] as $subfield) {
                $data[$subfield['code']][] = $subfield['data'];
            }
            if (!empty($data)) {
                $details[] = new PublicationDetails(
                    !empty($data['a']) ? implode('; ', $data['a']) . ' :' : '',
                    !empty($data['b']) ? implode('; ', $data['b']) . ',' : '',
                    implode('; ', $data['c'])
                );
            }
        }
        return $details;
    }

    /**
     * Get an array of publication detail lines combining information from
     * getPublicationDates(), getPublishers() and getPlacesOfPublication().
     *
     * @return array
     */
    public function getPublicationDetails(): array
    {
        if (
            str_starts_with($this->getUniqueID(), 'mzk.MZK03')
            && !empty($normalizedPublicationDetails = $this->getNormalizedPublicationDetails())
        ) {
            return $normalizedPublicationDetails;
        }
        return parent::getPublicationDetails();
    }

    /**
     * Has item links - field 994?
     *
     * @return bool
     */
    public function hasItemLinks(): bool
    {
        return !empty($this->getMarcReader()->getFields('994'));
    }

    /**
     * Get item links - field 994 enriched by title from record.
     *
     * @param string $type type of link (UP, DN or null to ignore) to return
     *
     * @return array
     */
    public function getItemLinks($type = null): array
    {
        $fields994 = $this->getStructuredDataFieldArray('994');
        $itemLinks = [];
        foreach ($fields994 as $field) {
            $id = $this->getSourceId() . '.' . $field['l'] . '-' . $field['b'];
            $linkType = $field['a'];
            if ($type != null && $type != $linkType) {
                continue;
            }
            $label = $field['n'];
            $itemLinks[$id] = [
                'label' => $label,
                'type'  => $linkType,
            ];
        }
        uasort($itemLinks, function ($a, $b) {
            return strnatcmp($a['label'], $b['label']);
        });
        $ids = array_keys($itemLinks);
        if ($this->recordLoader != null) {
            $records = $this->recordLoader->loadBatchForSource($ids);
            foreach ($records as $record) {
                $itemLinks[$record->getUniqueId()]['record'] = $record;
            }
        }
        return $itemLinks;
    }

    /**
     * Get publisher details
     *
     * @return string
     */
    public function getPublisherDetails(): string
    {
        $details = $this->getFirstFieldValue('260', ['a', 'b', 'c']);
        if (empty($details)) {
            $details = $this->getFirstFieldValue('264', ['a', 'b', 'c']);
        }
        return $details;
    }
}
