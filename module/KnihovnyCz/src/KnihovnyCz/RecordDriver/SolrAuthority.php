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
    public function getPseudonyms() {
        $pseudonyms = [];
        $names = $this->fields['pseudonym_name_display_mv'] ?? null;
        $ids = $this->fields['pseudonym_record_ids_display_mv'] ?? null;
        if ($names && $ids) {
            $pseudonyms = array_combine($names, $ids);
        }
        return $pseudonyms;
    }

    /**
     * Get authority's source.
     *
     * @return array
     */
    public function getSource()
    {
        return $this->fields['source_display_mv'] ?? [];
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

    public function getBibinfoForObalkyKnihV3()
    {
        return ['auth_id' => $this->getAuthorityId()];
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
     * Returns true, if authority has publications.
     *
     * @return bool
     */
    public function hasPublications()
    {
        // TODO: not implemented yet (and should be refactored)
        //$results = $this->searchController->getAuthorityPublicationsCount($this->getAuthorityId());
        $results = 10; //placeholder value
        return ($results > 1);
    }
    /**
     * Returns true, if there are publications about this authority.
     *
     * @return bool
     */
    public function hasPublicationsAbout()
    {
        // TODO: not implemented yet (and should be refactored)
        //$results = $this->searchController->getPublicationsAboutAvailableCount($this->getAuthorityId());
        $results = 10; //placeholder value
        return ($results > 0);
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
}

