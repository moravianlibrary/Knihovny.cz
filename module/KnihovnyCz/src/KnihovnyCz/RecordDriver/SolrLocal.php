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
     * Solr Id resolver
     *
     * @var \KnihovnyCz\ILS\Service\SolrIdResolver $resolver
     */
    protected $solrIdResolver;

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
                if (($linkedRecordId = $record['recordId'] ?? null) != null) {
                    $record = $this->recordLoader->load($linkedRecordId);
                    if ($record != null) {
                        $recordId = $record->getUniqueID();
                        $f996 = $record->fields['mappings996_display_mv'] ?? [];
                    }
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

        // Sort periodicals by year and issue descending
        $sortFunction = fn($a, $b) => strnatcmp($b['year'], $a['year']) ?: strnatcmp($b['issue'], $a['issue']);
        // Sort monography series ascending
        $issues = array_filter(array_map(fn($item) => $item['issue'], $items));
        if (empty($issues)) {
            $sortFunction = fn($a, $b) => strnatcmp($a['volume'], $b['volume']);
        }
        usort($items, $sortFunction);

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
        $ajaxFilters = count($filters['year']) > 1
            && $this->supportsAjaxHoldingsFilters();
        return empty($items) ? [] :
            [
                'holdings' => [
                    [
                        'location' => 'default',
                        'items' => $items,
                    ],
                ],
                'filters'  => $filters,
                'ajaxFilters' => $ajaxFilters,
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
     * Supports ajax holdings.
     *
     * @return bool
     */
    public function supportsAjaxHoldings(): bool
    {
        // We need to check also if driver name is not empty
        return $this->ils != null
            && !empty($this->ils->getDriverName($this->getUniqueID()))
            && $this->ils->getDriver()->supportsMethod('getHolding', ['id' => $this->getUniqueID()]);
    }

    /**
     * Supports ajax holdings filter.
     *
     * @return bool
     */
    public function supportsAjaxHoldingsFilters(): bool
    {
        if ($this->ils == null) {
            return false;
        }
        $config = $this->ils->getConfig('Holdings', ['id' => $this->getUniqueID()]) ?? [];
        $filters = $config['filters'] ?? [];
        return in_array('year', $filters)
            && in_array('volume', $filters);
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
     * @param ?string $type type of link (UP, DN or null to ignore) to return
     *
     * @return array
     */
    public function getItemLinks(?string $type = null): array
    {
        $itemLinks = $this->getItemLinksFrom994($type);
        $itemLinks += $this->parseItemLinks($type);
        if ($this->recordLoader != null) {
            $ids = array_column(array_filter($itemLinks, function ($record) {
                return !isset($record['title']) && isset($record['recordId']);
            }), 'recordId');
            $records = $this->recordLoader->loadBatchForSource($ids);
            $recordsById = [];
            foreach ($records as $record) {
                $recordsById[$record->getUniqueId()] = $record;
            }
            foreach ($itemLinks as &$itemLink) {
                $record = $recordsById[$itemLink['recordId']] ?? null;
                if ($record != null) {
                    $itemLink['title'] = $record->getTitle() . '  (' . $itemLink['label'] . ')';
                }
            }
        }
        return $itemLinks;
    }

    /**
     * Get item links from 994 field
     *
     * @param string $type type of link (UP, DN or null to ignore) to return
     *
     * @return array
     */
    protected function getItemLinksFrom994(?string $type = null): array
    {
        $itemLinks = [];
        $source = $this->getSourceId();
        $fields994 = $this->getStructuredDataFieldArray('994');
        foreach ($fields994 as $field) {
            if (empty($field['l']) && empty($field['b']) && empty($field['a']) && empty($field['n'])) {
                continue;
            }
            $base = $field['l'] ?? '';
            $id = $source . '.' . (empty($base) ? '' : $base . '-') . ($field['b'] ?? '');
            $linkType = $field['a'] ?? '';
            if ($type != null && $type != $linkType) {
                continue;
            }
            $label = $field['n'] ?? '';
            $itemLink = [
                'recordId' => $id,
                'label' => $label,
                'type'  => $linkType,
            ];
            if ($linkType == 'UP') {
                $itemLink['title'] = $this->translate('document_bound_in_a_composite_volume_order_text');
            }
            $itemLinks[] = $itemLink;
        }
        uasort($itemLinks, function ($a, $b) {
            return strnatcmp($a['label'], $b['label']);
        });
        return $itemLinks;
    }

    /**
     * Parse item links according to configuration for institution
     *
     * @param string $requiredType type
     *
     * @return array
     */
    protected function parseItemLinks(string $requiredType = null): array
    {
        $config = $this->recordConfig->Record->itemLinks;
        $itemLinksConfig = $config[$this->getSourceId()] ?? null;
        if ($itemLinksConfig == null) {
            return [];
        }
        $configItems = explode(':', $itemLinksConfig);

        [$field, $ind1, $ind2] = $this->parseTagSpecWithIndicators($configItems[0]);
        $idSubfield = $configItems[1];
        $solrQueryField = $configItems[2];
        $labelSubfields = str_split($configItems[3]);
        $useLabelAsTitle = ($configItems[4] ?? '') == 'title';

        $records = [];
        $fields = $this->getStructuredDataFieldArray($field);
        foreach ($fields as $fieldArray) {
            if ($ind1 != null && $ind1 != $fieldArray['ind1']) {
                continue;
            }
            if ($ind2 != null && $ind2 != $fieldArray['ind2']) {
                continue;
            }
            $label = trim(implode(' ', array_map(fn ($sf) => $fieldArray[$sf] ?? '', $labelSubfields)));
            if ($label == '') {
                $label = $this->translate('document_bound_in_a_composite_volume_order_text');
            }
            $id = trim($fieldArray[$idSubfield] ?? '');
            if ($id != '') {
                $records[$id] = $label;
            }
        }
        $type = (count($fields) > 1) ? 'DN' : 'UP';
        if ($requiredType != null && $requiredType != $type) {
            return [];
        }
        $resolverConfig = [
            'itemIdentifier'  => 'id',
            'solrQueryField'  => $solrQueryField,
            'sourceFilter'    => $this->getSourceId(),
            'separateIdParts' => false,
        ];
        $resolved = $this->solrIdResolver->convertToIdUsingSolr(array_keys($records), $resolverConfig);
        $itemLinks = [];
        foreach ($records as $itemIdentifier => $label) {
            $recordId = $resolved[$itemIdentifier] ?? null;
            $result = [
                'recordId' => $recordId,
                'label'    => $label,
                'type'     => $type,
            ];
            if ($useLabelAsTitle) {
                $result['title'] = $label;
            }
            $itemLinks[] = $result;
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

    /**
     * Attach Solr id resolver
     *
     * @param \KnihovnyCz\ILS\Service\SolrIdResolver $resolver resolver
     *
     * @return void
     */
    public function atttachSolrIdResolver(\KnihovnyCz\ILS\Service\SolrIdResolver $resolver)
    {
        $this->solrIdResolver = $resolver;
    }

    /**
     * Show information about RetrIS for some NKP records
     *
     * @return bool
     * @throws \Exception
     */
    public function showRetrisNkp(): bool
    {
        if (!str_starts_with($this->getUniqueID(), 'nkp.NKC01')) {
            return false;
        }
        $isPublishedInCzechia = substr($this->getMarcReader()->getField('008') ?? '', 15, 2) === 'xr';
        $publicationDate = intval($this->fields['publishDate_sort'] ?? 0);
        $isArchive = true;
        foreach ($this->getOfflineHoldings()['holdings'] ?? [] as $holding) {
            foreach ($holding['items'] ?? [] as $item) {
                if (($item['location'] ?? '') != 'NÁRODNÍ KONZERVAČNÍ FOND') {
                    $isArchive = false;
                    break 2;
                }
            }
        }
        return ($isArchive || $this->isTypePeriodical())
            && $publicationDate <= 1995
            && (($isPublishedInCzechia && $publicationDate >= 1900) || !$isPublishedInCzechia);
    }
}
