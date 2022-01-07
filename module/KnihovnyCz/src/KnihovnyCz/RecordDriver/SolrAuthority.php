<?php
/**
 * Knihovny.cz solr authority record driver
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

use VuFindSearch\Command\SearchCommand;

/**
 * Knihovny.cz solr authority record driver
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class SolrAuthority extends \KnihovnyCz\RecordDriver\SolrMarc
{
    /**
     * Get the full title of the record.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->fields['personal_name_display'] ?? '';
    }

    /**
     * Get the alternatives of the full name.
     *
     * @return array of alternative names
     */
    public function getAddedEntryPersonalNames()
    {
        return $this->fields['alternative_name_display_mv'] ?? [];
    }

    /**
     * Get the authority's pseudonyms.
     *
     * @return array
     */
    public function getPseudonyms()
    {
        $pseudonyms = [];
        $names = $this->fields['pseudonym_name_display_mv'] ?? [];
        $ids = $this->fields['pseudonym_record_ids_display_mv'] ?? [];
        if ($names && $ids) {
            $pseudonyms = array_combine($names, $ids);
        }
        return $pseudonyms ? $pseudonyms : [];
    }

    /**
     * Get authority's source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->fields['source_display_mv'][0] ?? '';
    }

    /**
     * Get the authority's name, shown as title of record.
     *
     * @return string
     */
    public function getHighlightedTitle()
    {
        return rtrim($this->getTitle(), ',');
    }

    /**
     * Get the authority's bibliographic details.
     *
     * @return array $field
     */
    public function getSummary()
    {
        return $this->fields['bibliographic_details_display_mv'] ?? [];
    }

    /**
     * Get the bibliographic details of authority.
     *
     * @return string $details
     */
    public function getBibliographicDetails()
    {
        return isset($this->fields['bibliographic_details_display_mv'])
            ? $this->fields['bibliographic_details_display_mv'][0] : '';
    }

    /**
     * Get id_authority.
     *
     * @return string
     */
    public function getAuthorityId()
    {
        return $this->fields['authority_id_display'] ?? '';
    }

    /**
     * Get count of results for given search
     *
     * @param string $field Field to search
     * @param string $value Value to search for
     *
     * @return int
     */
    protected function getCountByField(string $field, string $value)
    {
        $safeValue = addcslashes($value, '"');
        $query = new \VuFindSearch\Query\Query($field . ':"' . $safeValue . '"');
        $params = new \VuFindSearch\ParamBag(['hl' => ['false']]);
        $command = new SearchCommand($this->sourceIdentifier, $query, 0, 0, $params);
        return $this->searchService->invoke($command)->getResult()->getTotal();
    }

    /**
     * Returns true, if authority has publications.
     *
     * @return bool
     */
    public function hasPublications()
    {
        return 1 < $this->getCountByField(
            'authorCorporation_search_txt_mv',
            $this->getAuthorityId()
        );
    }

    /**
     * Returns true, if there are publications about this authority.
     *
     * @return bool
     */
    public function hasPublicationsAbout()
    {
        return 0 < $this->getCountByField(
            'subjectKeywords_search_txt_mv',
            $this->getAuthorityId()
        );
    }

    /**
     * Get link to search publications of authority.
     *
     * @return string|null
     */
    public function getPublicationsUrl()
    {
        $url = null;
        if ($this->hasPublications()) {
            $url = "/Search/Results?"
                . "sort=relevance&join=AND&type0[]=adv_search_author_corporation"
                . "&bool0[]=AND&searchTypeTemplate=advanced&lookfor0[]="
                . $this->getAuthorityId();
        }
        return $url;
    }

    /**
     * Get link to search publications about authority.
     *
     * @return string|null
     */
    public function getPublicationsAboutUrl()
    {
        $url = null;
        if ($this->hasPublicationsAbout()) {
            $url = "/Search/Results?"
                . "sort=relevance&join=AND&type0[]=adv_search_subject_keywords"
                . "&bool0[]=AND&searchTypeTemplate=advanced&lookfor0[]="
                . $this->getAuthorityId();
        }
        return $url;
    }

    /**
     * Get urls related to this record, publications of this authority
     *  and publications about this authority
     *
     * @return array
     */
    public function getRelatedUrls()
    {
        $urls = [];
        $publicationsUrl = $this->getPublicationsUrl();
        $publicationsAboutUrl = $this->getPublicationsAboutUrl();
        if ($publicationsUrl) {
            $urls[] = [
                'url' => $publicationsUrl,
                'desc' => 'Show publications of this person',
            ];
        }
        if ($publicationsAboutUrl) {
            $urls[] = [
                'url' => $publicationsAboutUrl,
                'desc' =>  'Show publications about this person',
            ];
        }
        return $urls;
    }

    /**
     * Returns array with one key: authority_id
     *
     * @param string $size Size of thumbnail (small, medium or large -- small is
     * default).
     *
     * @return string|array|bool
     */
    public function getThumbnail($size = 'small')
    {
        return [
            'recordid' => $this->getUniqueID(),
            'source' => $this->getSourceIdentifier(),
            'size' => $size,
            'title' => $this->getTitle(),
            'nbn' => $this->getAuthorityId(),
        ];
    }
}
