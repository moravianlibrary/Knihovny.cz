<?php
/**
 * Knihovny.cz solr library record driver
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

class SolrLibrary extends \KnihovnyCz\RecordDriver\SolrMarc
{

    /**
     * @var \Zend\Config\Config
     */
    protected $facetsConfig = null;

    public function getParentRecordID()
    {
        return $this->fields['id'] ?? '';
    }

    /**
     * Get the full title of the record
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->fields['name_display'] ?? '';
    }

    /**
     * Get an array of note about the libraryhours
     *
     * @return array
     */
    public function getLibraryHours()
    {
        $result = [];
        $hours = $this->fields['hours_display'] ?? '';
        if (!empty($hours)) {
            $days = explode("|", $hours);
            foreach ($days as $day) {
                $parts = explode(" ", trim($day), 2);
                $result[$parts[0]] = $parts[1];
            }
        }
        return $result;
    }

    /**
     * Get date
     *
     * @return String
     */
    public function getLastUpdated()
    {
        return $this->fields['lastupdated_display'] ?? '';
    }

    /**
     * Get an array of note about the libraryname
     *
     * @return array
     */
    public function getLibraryAddress()
    {
        return $this->fields['address_display_mv'] ?? [];
    }

    /**
     * Get an array of library ico and dicn
     *
     * @return string
     */
    public function getIco()
    {
        return $this->fields['ico_display'] ?? '';
    }

    /**
     * Get an array of library ico and dicn
     *
     * @return string
     */
    public function getLibNote()
    {
        return $this->fields['note_display'] ?? '';
    }

    /**
     *
     * @return string
     */
    public function getLibNote2()
    {
        return $this->fields['note2_display'] ?? '';
    }

    /**
     *
     * @return string
     */
    public function getSigla()
    {
        return $this->fields['sigla_display'] ?? '';
    }

    /**
     *
     * @return array
     */
    public function getLibUrls()
    {
        $result = [];
        $urls = $this->fields['url_display_mv'] ?? null;
        if (is_array($urls)) {
            $filter = function ($url) {
                $parts = explode("|", trim($url), 2);
                $parts = array_map('trim', $parts);
                return [
                    'url' => $parts[0] ?? null,
                    'name' => $parts[1] ?? $parts[0] ?? null,
                ];
            };
            $result = array_map($filter, $urls);
        }
        return $result;
    }

    /**
     *
     * @return array
     */
    public function getLibBranch()
    {
        return $this->fields['branch_display_mv'] ?? [];
    }

    /**
     *
     * @return array
     */
    public function getLibResponsibility()
    {
        return $this->fields['responsibility_display_mv'] ?? [];
    }

    /**
     *
     * @return array
     */
    public function getPhone()
    {
        return $this->fields['phone_display_mv'] ?? [];
    }

    /**
     *
     * @return array
     */
    public function getEmail()
    {
        return $this->fields['email_display_mv'] ?? [];
    }

    /**
     *
     * @return array
     */
    public function getService()
    {
        return $this->fields['services_display_mv'] ?? [];
    }

    /**
     *
     * @return array
     */
    public function getFunction()
    {
        return $this->fields['function_display_mv'] ?? [];
    }

    /**
     *
     * @return array
     */
    public function getProject()
    {
        return $this->fields['projects_display_mv'] ?? [];
    }

    /**
     *
     * @return array
     */
    public function getType()
    {
        return $this->fields['type_display_mv'] ?? [];
    }

    /**
     *
     * @return array
     */
    public function getMvs()
    {
        return $this->fields['mvs_display_mv'] ?? [];
    }

    /**
     *
     * @return array
     */
    public function getBranchUrl()
    {
        return $this->fields['branchurl_display_mv'] ?? [];
    }

    public function getBookSearchFilter()
    {
        $institution = $this->fields['cpk_code_display'] ?? null;
        $institutionsMappings = $institution ? $this->facetsConfig->InstitutionsMappings->toArray() : null;
        return $institutionsMappings[$institution] ?? null;
    }

    /**
     * get gps coordinates of library
     *
     * @return array
     */
    public function getGpsCoordinates()
    {
        $gps = $this->fields['gps_display'] ?? '';
        $coords = [];
        if ($gps != '' ) {
            list($coords['lat'], $coords['lng']) = explode(" ", $gps, 2);
        }
        return $coords;
    }

    public function hasAdditionalInfo()
    {
        return (!empty($this->getSigla()) || !empty($this->getLastUpdated()) );
    }

    public function hasContacts()
    {
        return (!empty($this->getPhone())
            || !empty($this->getEmail())
            || !empty($this->getLibResponsibility()));
    }

    public function hasServices()
    {
        return (!empty($this->getService())
            || !empty($this->getFunction())
            || !empty($this->getProject()));
    }

    public function hasBranches()
    {
        return !empty($this->getLibBranch());
    }

    /**
     * Get handler for related
     *
     * @return array
     */
    public function getFilterParamsForRelated()
    {
        return ['handler' => 'morelikethislibrary'];
    }

    /**
     * Get Regional Library
     *
     * @return array
     */
    public function getRegLibrary()
    {
        $library       = $this->fields['reg_lib_id_display_mv'] ?? [];
        $parsedLibrary = empty($library) ? [] : explode('|', $library[0]);
        return empty($parsedLibrary) ? [] : ['id' => $parsedLibrary[0], 'name' => $parsedLibrary[1]];
    }

    /**
     * @param \Zend\Config\Config $facetsConfig
     */
    public function attachFacetsConfig($facetsConfig)
    {
        $this->facetsConfig = $facetsConfig;
    }
}

