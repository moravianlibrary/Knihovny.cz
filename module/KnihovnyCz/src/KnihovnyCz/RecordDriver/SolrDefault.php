<?php
/**
 * Knihovny.cz solr default record driver
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

use VuFind\Exception\RecordMissing as RecordMissingException;

/**
 * Knihovny.cz solr default record driver
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class SolrDefault extends \VuFind\RecordDriver\SolrDefault
{
    use Feature\BuyLinksTrait;
    use Feature\ObalkyKnihTrait;
    use Feature\WikidataTrait;
    use \VuFind\Cache\CacheTrait;

    public const EDD_SUBTYPE_ARTICLE = 'article';
    public const EDD_SUBTYPE_SELECTION = 'selection';

    /**
     * These Solr fields should be used for snippets if available (listed in order
     * of preference).
     *
     * @var array
     */
    protected $preferredSnippetFields = [
        'toc_txt_mv', 'fulltext'
    ];

    /**
     * Record data formatter key
     *
     * @return string
     */
    protected string $recordDataFormatterKey = 'core';

    /**
     * Record data description
     *
     * @return string
     */
    protected string $recordDataTypeDescription = "Bibliographic Details";

    /**
     * Parent record
     *
     * @var \VuFind\RecordDriver\AbstractBase|null
     */
    protected $parentRecord = null;

    /**
     * Record loader
     *
     * @var \VuFind\Record\Loader|null
     */
    protected $recordLoader = null;

    /**
     * Record factory
     *
     * @var \VuFind\RecordDriver\PluginManager
     */
    protected $recordFactory;

    /**
     * Library id mappings (by source)
     *
     * @var \Laminas\Config\Config
     */
    protected $libraryIdMappings;

    /**
     * Auth Manager
     *
     * @var \VuFind\Auth\Manager
     */
    protected $authManager;

    /**
     * Get the publishers of the record.
     *
     * @return array
     */
    public function getPublishers()
    {
        return $this->fields['publisher_display_mv'] ?? [];
    }

    /**
     * Get formats for display
     *
     * @return array
     */
    public function getFormats()
    {
        return $this->fields['format_display_mv'] ?? [];
    }

    /**
     * Returns parent record ID from SOLR
     *
     * @return string
     */
    public function getParentRecordID()
    {
        return $this->fields['parent_id_str'] ?? null;
    }

    /**
     * Identificator of record source
     *
     * @return string
     * @throws \Exception
     */
    public function getSourceId()
    {
        [$source] = explode('.', $this->getUniqueID());
        return $source;
    }

    /**
     * Returns first of ISSNs, ISBNs and ISMNs from SOLR
     *
     * @return string
     */
    public function getIsn()
    {
        return $this->fields['isbn'][0]
            ?? $this->fields['issn'][0]
            ?? $this->fields['ismn_int_mv'][0]
            ?? false;
    }

    /**
     * Get text that can be displayed to represent this record in
     * breadcrumbs.
     *
     * @return string Breadcrumb text to represent this record.
     */
    public function getBreadcrumb()
    {
        return $this->getTitle();
    }

    /**
     * Get the full title of the record.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->fields['title_display'] ?? '';
    }

    /**
     * Get title of record. This is for compatibility with original VuFind
     *
     * @return string
     */
    public function getShortTitle()
    {
        return $this->getTitle();
    }

    /**
     * Get the subtitle of the record.
     *
     * @return string
     */
    public function getSubtitle()
    {
        return $this->fields['title_sub_display'] ?? '';
    }

    /**
     * Get record type for citation
     *
     * @return string
     */
    public function getCitationRecordType()
    {
        if (!empty($this->fields['citation_record_type_str'])) {
            return $this->fields['citation_record_type_str'];
        }
        $parent = $this->getParentRecord();
        return ($parent !== null) ? $parent->getCitationRecordType() : '';
    }

    /**
     * Pick one line from the highlighted text (if any) to use as a snippet.
     *
     * @return mixed False if no snippet found, otherwise associative array
     * with 'snippet' and 'caption' keys.
     */
    public function getHighlightedSnippet()
    {
        // Only process snippets if the setting is enabled:
        if ($this->snippet) {
            // First check for preferred fields:
            foreach ($this->preferredSnippetFields as $current) {
                if (isset($this->highlightDetails[$current][0])) {
                    return [
                        'snippet' => $this->highlightDetails[$current][0],
                        'caption' => $this->getSnippetCaption($current)
                    ];
                }
            }
        }
        // If we got this far, no snippet was found:
        return false;
    }

    /**
     * Get authority ID of main author.
     *
     * @return array
     */
    public function getPrimaryAuthorsIds()
    {
        return isset($this->fields['author_authority_id_display'])
            ? [$this->fields['author_authority_id_display']] : [];
    }

    /**
     * Get the main author of the record.
     *
     * @return array
     */
    public function getPrimaryAuthors()
    {
        if (isset($this->fields['author_display'])) {
            return [$this->fields['author_display']];
        }
        return [];
    }

    /**
     * Get the main author of the record for sorting purposes.
     *
     * @return string|null
     */
    public function getPrimaryAuthorForSorting()
    {
        return $this->fields['author_sort_cz'] ?? null;
    }

    /**
     * Get an array of all secondary authors (complementing getPrimaryAuthor()).
     *
     * @return array
     */
    public function getSecondaryAuthors()
    {
        return $this->fields['author2_display_mv'] ?? [];
    }

    /**
     * Get the corporate author of the record.
     *
     * @return array
     */
    public function getCorporateAuthors()
    {
        if (isset($this->fields['corp_author_display'])) {
            return [$this->fields['corp_author_display']];
        }
        return [];
    }

    /**
     * Get an array of authority Ids of all secondary authors in the same order
     * and amount as getSecondaryAuthors() method from author2 solr field.
     *
     * @return array
     */
    public function getSecondaryAuthorsIds()
    {
        return $this->fields['author2_authority_id_display_mv'] ?? [];
    }

    /**
     * Get the publication dates of the record.  See also getDateSpan().
     *
     * @return array
     */
    public function getPublicationDates(): array
    {
        return $this->fields['publishDate_display'] ?? [];
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISBNs()
    {
        // If ISBN is in the index, it should automatically be an array... but if
        // it's not set at all, we should normalize the value to an empty array.
        return (isset($this->fields['isbn_display_mv'])
            && is_array($this->fields['isbn_display_mv']))
            ? $this->fields['isbn_display_mv'] : [];
    }

    /**
     * Get an array of summary strings for the record.
     *
     * @return array
     */
    public function getSummary()
    {
        $summary = $this->fields['summary_display_mv'] ?? [];
        if (empty($summary)) {
            /**
             * Parent record
             *
             * @var \KnihovnyCz\RecordDriver\SolrDefault|null $parent
             */
            $parent = $this->getParentRecord();
            $summary = ($parent !== null) ? $parent->getSummary() : [];
        }
        return $summary;
    }

    /**
     * Get raw data of monographic series
     *
     * @return array
     */
    protected function getMonographicSeriesFieldData()
    {
        return $this->fields['monographic_series_display_mv'] ?? [];
    }

    /**
     * Get monographic series for display
     *
     * @return array
     */
    public function getMonographicSeries()
    {
        $result = [];
        $seriesField = $this->getMonographicSeriesFieldData();
        if (!$seriesField) {
            $parentRecord = $this->getParentRecord();
            if ($parentRecord !== null) {
                $seriesField = (array)$parentRecord->tryMethod(
                    'getMonographicSeriesFieldData'
                );
            }
        }
        $params = http_build_query(
            [
                'type0[]' => 'adv_search_monographic_series',
                'join' => 'AND',
                'searchTypeTemplate' => 'advanced',
                'page' => '1',
                'bool0[]' => 'AND',
            ],
            '',
            '&amp;'
        );
        foreach ($seriesField as $serie) {
            $result[] = [
                'url' => '/Search/Results?lookfor0[]='
                    . urlencode(explode("|", $serie)[0])
                    . '&amp;' . $params,
                'desc' => str_replace('|', ' | ', $serie),
            ];
        }
        return $result;
    }

    /**
     * Is record available in Ziskej service?
     *
     * @return bool
     */
    public function getZiskejBoolean(): bool
    {
        $ziskejBooleanLocal = $this->fields['ziskej_boolean'] ?? null;

        $parent = $this->getParentRecord();
        $ziskejBooleanParent
            = null !== $parent ? ($parent->fields['ziskej_boolean'] ?? null) : null;

        return null !== $ziskejBooleanParent
            ? $ziskejBooleanParent
            : (null !== $ziskejBooleanLocal ? $ziskejBooleanLocal : false);
    }

    /**
     * If record is available in ZiskejEDD service
     *
     * @return bool
     */
    public function getEddBoolean(): bool
    {
        $eddBooleanLocal = $this->fields['edd_boolean'] ?? null;

        $parent = $this->getParentRecord();
        $eddBooleanParent
            = null !== $parent ? ($parent->fields['edd_boolean'] ?? null) : null;

        return null !== $eddBooleanParent
            ? $eddBooleanParent
            : (null !== $eddBooleanLocal ? $eddBooleanLocal : false);
    }

    /**
     * Return Ziskej EDD subtype of record based on record formats
     *
     * @return string
     */
    public function getEddSubtype(): string
    {
        return in_array('0/ARTICLES/', $this->getFormats())
            ? self::EDD_SUBTYPE_ARTICLE
            : self::EDD_SUBTYPE_SELECTION;
    }

    /**
     * Return if Periodical type
     *
     * @return bool
     */
    public function isTypePeriodical(): bool
    {
        return in_array('0/PERIODICALS/', $this->getFormats());
    }

    /**
     * Return if Book type
     *
     * @return bool
     */
    public function isTypeBook(): bool
    {
        return in_array('0/BOOKS/', $this->getFormats());
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     * - desc: URL description text to display (optional)
     * - url: fully-formed URL (required if 'route' is absent)
     * - destination: web or digital library
     * - status: access status
     * - source: source of data
     *
     * @return array
     */
    public function getLinks()
    {
        $parentRecord = $this->getParentRecord();
        return ($parentRecord !== null)
            ? (array)$parentRecord->tryMethod('get856Links')
            : [];
    }

    /**
     * Does record have some links to show?
     *
     * @return bool
     */
    public function hasLinks(): bool
    {
        $parentRecord = $this->getParentRecord();
        if ($parentRecord !== null) {
            return (bool)$parentRecord->tryMethod('has856Links');
        }
        return false;
    }

    /**
     * Get links from marc field 856
     *
     * @return array
     */
    protected function get856Links()
    {
        return $this->getLinksFromSolrField('url');
    }

    /**
     * Does record has links from marc field 856
     *
     * @return bool
     */
    protected function has856Links(): bool
    {
        return (bool)($this->fields['url'] ?? false);
    }

    /**
     * Get parent record
     *
     * @return \VuFind\RecordDriver\AbstractBase|null
     *
     * @throws \Exception
     */
    public function getParentRecord()
    {
        if ($this->parentRecord === null && isset($this->fields['parent_data'])) {
            $this->parentRecord = $this->recordFactory
                ->getSolrRecord($this->fields['parent_data']);
        }
        if ($this->parentRecord === null && $this->recordLoader !== null
            && ($parentRecordId = $this->getParentRecordID()) !== null
        ) {
            try {
                $this->parentRecord = $this->recordLoader->load($parentRecordId);
            } catch (RecordMissingException $exception) {
                // If there is no parent record (e.g. this is parent), we could
                // safely keep parent record variable at null
            }
        }
        return $this->parentRecord;
    }

    /**
     * Used in ajax to get sfx url
     *
     * @return array
     */
    public function getChildrenIds()
    {
        return $this->fields['local_ids_str_mv'] ?? [];
    }

    /**
     * Return true if the record is one of the duplicate records in group
     *
     * @return bool
     */
    public function hasDeduplicatedRecords()
    {
        $parent = $this->getParentRecord();
        return ($parent !== null) ?
            !empty((array)$parent->tryMethod('getChildrenIds')) : false;
    }

    /**
     * Return deduplicated records - array with key as institution source and
     * value with record ids or false if not supported
     *
     * @return array|false
     */
    public function getDeduplicatedRecords()
    {
        $results = [];
        $localIds = $this->getDeduplicatedRecordIds();
        foreach ($localIds as $localId) {
            [$source] = explode('.', $localId);
            if (!isset($results[$source])) {
                $results[$source] = [ $localId ];
            } else {
                $results[$source][] = $localId;
            }
        }
        foreach ($results as $source => $ids) {
            sort($ids);
        }
        /**
         * User model
         *
         * @var \KnihovnyCz\Db\Row\User|false $user
         */
        $user = $this->authManager->isLoggedIn();
        if ($user) {
            $prefixes = $user->getLibraryPrefixes();
            array_unshift($prefixes, $this->getSourceId());
            uksort(
                $results,
                function ($a, $b) use ($prefixes) {
                    $a = array_search($a, $prefixes);
                    $a = ($a !== false) ? $a : PHP_INT_MAX;
                    $b = array_search($b, $prefixes);
                    $b = ($b !== false) ? $b : PHP_INT_MAX;
                    return (int)$a - (int)$b;
                }
            );
        }
        return $results;
    }

    /**
     * Return array of all deduplicated record ids
     *
     * @return array
     */
    public function getDeduplicatedRecordIds()
    {
        $parent = $this->getParentRecord() ?? $this;
        return $parent->tryMethod('getChildrenIds') ?? [];
    }

    /**
     * Attach a Record Loader
     *
     * @param \VuFind\Record\Loader $recordLoader Record Loader
     *
     * @return void
     */
    public function attachRecordLoader(\VuFind\Record\Loader $recordLoader)
    {
        $this->recordLoader = $recordLoader;
    }

    /**
     * Attach a Record factory
     *
     * @param \VuFind\RecordDriver\PluginManager $recordFactory Record factory
     *
     * @return void
     */
    public function attachRecordFactory(
        \VuFind\RecordDriver\PluginManager $recordFactory
    ) {
        $this->recordFactory = $recordFactory;
    }

    /**
     * Attach libary id mappings
     *
     * @param \Laminas\Config\Config $mappings Mappings from config
     *
     * @return void
     */
    public function attachLibraryIdMappings(\Laminas\Config\Config $mappings)
    {
        $this->libraryIdMappings = $mappings;
    }

    /**
     * Attach auth manager
     *
     * @param \VuFind\Auth\Manager $authManager Auth manager
     *
     * @return void
     */
    public function attachAuthManager($authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Get owning library id
     *
     * @return string|null
     */
    public function getOwningLibraryId(): ?string
    {
        $source = $this->getSourceId();
        return $this->libraryIdMappings[$source] ?? null;
    }

    /**
     * Get related record data
     *
     * @return array
     */
    public function getSimilarFromSolrField(): array
    {
        $field = $this->fields['similar_display_mv'] ?? [];
        return array_map('json_decode', $field);
    }

    /**
     * Deduplicate author information into associative array with main/corporate/
     * secondary keys.
     *
     * @param array $dataFields An array of extra data fields to retrieve (see
     * getAuthorDataFields)
     *
     * @return array
     */
    public function getDeduplicatedAuthors($dataFields = ['role'])
    {
        return parent::getDeduplicatedAuthors(array_merge($dataFields, ['id']));
    }

    /**
     * Get the sigla for display
     *
     * @return string|null
     */
    public function getSiglaDisplay(): ?string
    {
        return $this->fields['sigla_display'] ?? null;
    }

    /**
     * Get the geolocation
     *
     * @return array|null
     *
     * @throws \Exception
     */
    public function getGeoLocation(): ?array
    {
        $geo = $this->fields['long_lat_display_mv'] ?? null;
        if ($geo == null && ($parent = $this->getParentRecord()) != null) {
            $parentFields = $parent->getRawData();
            if (isset($parentFields['long_lat_str'])) {
                $geo = [ $parentFields['long_lat_str'] ];
            }
        }
        return $geo;
    }

    /**
     * Get id from field 001 of marc record (indexed in solr)
     *
     * @return string
     */
    protected function getIdFrom001(): string
    {
        return $this->fields['id001_str'] ?? '';
    }

    /**
     * Get links of serials to library catalogue
     *
     * @return array
     */
    public function getSerialLinks(): array
    {
        return $this->getLinksFromSolrField('catalog_serial_link_display_mv');
    }

    /**
     * General function for getting links
     *
     * @param string $field Solr index field
     *
     * @return array
     */
    protected function getLinksFromSolrField(string $field = 'url'): array
    {
        $rawLinks = $this->fields[$field] ?? [];
        $links = [];
        foreach ($rawLinks as $rawLink) {
            $parts = explode("|", $rawLink);
            $destination = (substr($parts[0], 0, 4) === 'kram')
                ? 'Digital library'
                : ($parts[3] == 'catalog_serial_link' ? 'Library catalogue' : 'Web');
            $links[] = [
                'destination' => $destination,
                'status' => $parts[1] != '' ? $parts[1] : null,
                'url' => $parts[2] != '' ? $parts[2] : null,
                'desc' => $parts[3] != '' ? $parts[3] : null,
                'source' => $parts[0] != '' ? $parts[0] : null
            ];
        }
        return $links;
    }

    /**
     * Get source title
     *
     * @return string|null
     * @throws \Exception
     */
    /**
     * Get record data formatter key
     *
     * @return string
     */
    public function getRecordDataFormatterKey(): string
    {
        return $this->recordDataFormatterKey;
    }

    /**
     * Get record data type description
     *
     * @return string
     */
    public function getRecordDataTypeDescription(): string
    {
        return $this->recordDataTypeDescription;
    }

    /**
     * Method to ensure uniform cache keys for cached VuFind objects.
     *
     * @param string|null $suffix Optional suffix that will get appended to the
     * object class name calling getCacheKey()
     *
     * @return string
     */
    protected function getCacheKey($suffix = null)
    {
        $id = str_replace('.', '_', $this->getUniqueID());
        return 'record_' . $id . '_' . $suffix;
    }

    /**
     * Return title of source document
     *
     * @return ?string
     */
    public function getSourceTitleFacet(): ?string
    {
        $parent = $this->getParentRecord();
        return null !== $parent
            ? ($parent->fields['source_title_facet'] ?? null)
            : null;
    }

    /**
     * Get place of publication place
     *
     * @return string|null
     *
     * @throws \Exception
     */
    public function getPlaceOfPublication(): ?string
    {
        $field = 'placeOfPublication_txt_mv';

        if (isset($this->fields[$field])) {
            $places = $this->fields[$field];
            if (!empty($places[0])) {
                return $places[0];
            }
        }

        $parent = $this->getParentRecord();
        if ($parent !== null && isset($parent->fields[$field])) {
            $places = $parent->fields[$field];
            if (!empty($places[0])) {
                return $places[0];
            }
        }

        return null;
    }

    /**
     * Get date published
     *
     * @return string|null
     *
     * @throws \Exception
     */
    public function getPublishDate(): ?string
    {
        $dates = $this->getPublicationDates();
        if (!empty($dates[0])) {
            return $dates[0];
        }

        $parent = $this->getParentRecord();
        if ($parent !== null) {
            $dates = $parent->getPublicationDates();
            if (!empty($dates[0])) {
                return $dates[0];
            }
        }

        return null;
    }

    /**
     * Get place of publication and publish date
     *
     * @return string|null
     *
     * @throws \Exception
     */
    public function getPlaceOfPublicationAndPublishDate(): ?string
    {
        $place = $this->getPlaceOfPublication();
        $date = $this->getPublishDate();

        if (!empty($place) && !empty($date)) {
            return sprintf('%s, %s', $place, $date);
        }

        return $place ?? $date ?? null;
    }

    /**
     * Get primary authors
     *
     * @return string|null
     */
    public function getPrimaryAuthorsString(): ?string
    {
        $authors = $this->getPrimaryAuthors();
        return count($authors) ? implode(', ', $authors) : null;
    }

    /**
     * Get ISBN
     *
     * @return string|null
     */
    public function getIsbn(): ?string
    {
        return !empty($this->getCleanISBN()) ? (string)$this->getCleanISBN() : null;
    }
}
