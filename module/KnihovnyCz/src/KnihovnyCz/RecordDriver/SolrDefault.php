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

class SolrDefault extends \VuFind\RecordDriver\SolrDefault
{
    use BuyLinksTrait;

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
     * @var \VuFind\RecordDriver\AbstractBase|\KnihovnyCz\RecordDriver\SolrDefault|null
     */
    protected $parentRecord = null;

    /**
     * @var \VuFind\Record\Loader|null
     */
    protected $recordLoader = null;

    /**
     * Library id mappings (by source)
     *
     * @var \Laminas\Config\Config
     */
    protected $libraryIdMappings;

    /**
     * Get the publishers of the record.
     *
     * @return array
     */
    public function getPublishers()
    {
        return $this->fields['publisher_display_mv'] ?? [];
    }

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
        return $this->fields['parent_id_str'] ?? '';
    }

    public function getSourceId()
    {
        list ($source) = explode('.', $this->getUniqueID());
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
    public function getTitle()
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

    public function getSubtitle()
    {
        return $this->fields['title_sub_display'] ?? '';
    }

    public function getCitationRecordType()
    {
        return $this->fields['citation_record_type_str'] ?? '';
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
     * @return string
     */
    public function getMainAuthorAuthorityRecordId()
    {
        return $this->fields['author_authority_id_display'] ?? false;
    }

    /**
     * Returns name of the Author to display
     *
     * @deprecated Used in ajax controller, should be used getPrimaryAuthor at call
     * @return     string|NULL
     */
    public function getDisplayAuthor()
    {
        return $this->getPrimaryAuthors()[0];
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
    public function getSecondaryAuthoritiesRecordIds()
    {
        return $this->fields['author2_authority_id_display_mv'] ?? [];
    }

    /**
     * Get the publication dates of the record.  See also getDateSpan().
     *
     * @return array
     */
    public function getPublicationDates()
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
        if (empty($summary)){
            $summary = ($parent = $this->getParentRecord())
                ? $parent->getSummary() : [];
        }
        return $summary;
    }

    protected function getMonographicSeriesFieldData()
    {
        return $this->fields['monographic_series_display_mv'] ?? [];
    }

    public function getMonographicSeries()
    {
        $result = [];
        $seriesField = $this->getMonographicSeriesFieldData();
        if (!$seriesField) {
            $parentRecord = $this->getParentRecord();
            if ($parentRecord !== null) {
                $seriesField = (array)$parentRecord->tryMethod('getMonographicSeriesFieldData');
            }
        }
        foreach ($seriesField as $serie) {
            $result[] = [
                'url' => '/Search/Results?lookfor0[]='
                    . urlencode(explode("|", $serie)[0])
                    . '&amp;type0[]=adv_search_monographic_series&amp;join=AND&amp;searchTypeTemplate=advanced&amp;page=1&amp;bool0[]=AND',
                'desc' => str_replace('|', ' | ', $serie),
            ];
        }
        return $result;
    }

    public function getZiskejBoolean() : bool
    {
        return $this->fields['ziskej_boolean'] ?? false;
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     *      desc: URL description text to display (optional)
     *      url: fully-formed URL (required if 'route' is absent)
     *      destination: web or digital library
     *      status: access status
     *      source: source of data
     *
     * @return array
     */
    public function getLinks()
    {
        $links = [];
        $parentRecord = $this->getParentRecord();
        if ($parentRecord !== null ) {
            $rawLinks = (array)$parentRecord->tryMethod('get856Links');
            foreach ($rawLinks as $rawLink) {
                $parts = explode("|", $rawLink);
                $link = [
                    'destination' => (substr($parts[0], 0, 4) === 'kram')
                        ? 'Digital library' : 'Web',
                    'status' => $parts[1] != '' ? $parts[1] : null,
                    'url' => $parts[2] != '' ? $parts[2] : null,
                    'desc' => $parts[3] != '' ? $parts[3] : null,
                    'source' => $parts[0] != '' ? $parts[0] : null
                ];
                $links[] = $link;
            }
        }
        return $links;
    }

    protected function get856Links()
    {
        return isset($this->fields['url']) ? $this->fields['url'] : [];
    }

    /**
     * Get parent record
     *
     * @return \VuFind\RecordDriver\AbstractBase|\KnihovnyCz\RecordDriver\SolrDefault|null
     */
    public function getParentRecord()
    {
        if ($this->parentRecord === null && $this->recordLoader !== null) {
            $parentRecordId = $this->getParentRecordID();
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
        return !empty((array)$this->getParentRecord()->tryMethod('getChildrenIds'));
    }

    /**
     * Return array of all record ids (with their source institution) deduplicated
     * with this record
     *
     * @return array
     */
    public function getDeduplicatedRecords()
    {
        return array_map(
            function ($localId) {
                list($source) = explode('.', $localId);
                return [
                    'source' => $source,
                    'id' => $localId,
                ];
            }, (array)$this->getParentRecord()->tryMethod('getChildrenIds')
        );
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
     * Get owning library id
     *
     * @return string|null
     */
    public function getOwningLibraryId(): ?string
    {
        $source = $this->getSourceId();
        return $this->libraryIdMappings[$source] ?? null;
    }

    public function getSimilarFromSolrField(): array
    {
        $field = $this->fields['similar_display_mv'] ?? [];
        return array_map('json_decode', $field);
    }
}
