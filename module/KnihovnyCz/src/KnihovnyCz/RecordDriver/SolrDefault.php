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

class SolrDefault extends \VuFind\RecordDriver\SolrDefault {

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
     * These Solr fields should NEVER be used for snippets.  (We exclude author
     * and title because they are already covered by displayed fields; we exclude
     * spelling because it contains lots of fields jammed together and may cause
     * glitchy output; we exclude ID because random numbers are not helpful).
     *
     * @var array
     */
    protected $forbiddenSnippetFields = [
        'author', 'author_autocomplete', 'author_display', 'author_facet_str_mv',
        'author-letter', 'author_search', 'author_sort_str', 'author_str_mv',
        'authorCorporation_search_txt_mv', 'ctrlnum', 'id','publishDate',
        'source_title_facet_str', 'sourceTitle_search_txt_mv', 'spelling',
        'spellingShingle',  'title',  'title_auth', 'title_autocomplete',
        'title_display',  'title_full', 'title_fullStr',
        'titleSeries_search_txt_mv', 'title_short', 'title_sort', 'title_sub',
    ];

    /**
     * @var \VuFind\RecordDriver\SolrDefault|null
     */
    protected $parentRecord = null;

    /**
     * @var \VuFind\Record\Loader|null
     */
    protected $recordLoader = null;

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

    /**
     * Returns first of ISSNs, ISBNs and ISMNs from SOLR
     *
     * @return  string
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
     * @return string|NULL
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
        return $this->fields['summary_display_mv'] ?? [];
    }

    public function getMonographicSeries($searchAlsoInParentRecord = true)
    {
        $series = $this->fields['monographic_series_display_mv'] ?: false;
        if (! $series && $searchAlsoInParentRecord) {
            $series = $this->getParentRecordDriver()->getMonographicSeries(false);
        }
        return $series;
    }

    public function getMonographicSeriesUrl(string $serie)
    {
        $mainSerie = explode("|", $serie)[0];
        return '/Search/Results?lookfor0[]=' . urlencode($mainSerie)
            . '&amp;type0[]=adv_search_monographic_series&amp;join=AND&amp;searchTypeTemplate=advanced&amp;page=1&amp;bool0[]=AND';
    }

    public function getMonographicSeriesTitle(string $serie)
    {
        return implode(" | ", explode("|", $serie));
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
            $rawLinks = $parentRecord->get856Links();
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
     * @return \VuFind\RecordDriver\AbstractBase|\VuFind\RecordDriver\SolrDefault|null
     */
    public function getParentRecord()
    {
        if ($this->parentRecord === null && $this->recordLoader !== null) {
            $parentRecordId = $this->getParentRecordID();
            $this->parentRecord = $this->recordLoader->load($parentRecordId);
        }
        return $this->parentRecord;
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


}