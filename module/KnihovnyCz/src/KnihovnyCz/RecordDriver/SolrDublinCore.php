<?php
/**
 * Knihovny.cz solr dublin core record driver
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

class SolrDublinCore extends \KnihovnyCz\RecordDriver\SolrDefault
{

    protected $xmlCache = null;

    /**
     * Get all subject headings associated with this record.  Each heading is
     * returned as an array of chunks, increasing from least specific to most
     * specific.
     *
     * @param bool $extended Whether to return a keyed array with the following
     * keys:
     * - heading: the actual subject heading chunks
     * - type: heading type
     * - source: source vocabulary
     *
     * @return array
     */
    public function getAllSubjectHeadings($extended = false)
    {
        $dc = $this->parseXML();
        $value = $dc->xpath('//dc:subject');
        $ret = [];
        foreach ($value as $part) {
            if (!preg_match('/^([\W0-9]+|neuvedeno|n[ae]zadáno)$/', $part))
            {
                $ret[] = (string) $part;
            }
        }
        return empty($value) ? [] : $ret;
    }

    /**
     * Get the full title of the record.
     *
     * @return string
     */
    public function getTitle()
    {
        var_dump($this->fields);
        return $this->fields['title'] ?? '';
    }

    /**
     * Get the subtitle of the record.
     *
     * @return string
     */
    public function getSubtitle()
    {
        return $this->fields['title_sub'] ?? '';
    }

    /**
     * Get the title of the item that contains this record (i.e. MARC 773s of a
     * journal).
     *
     * @return string
     */
    public function getContainerTitle()
    {
        return isset($this->fields['container_title']) ? $this->fields['container_title'] : '';
    }

    /**
     * Get an array of all the languages associated with the record.
     *
     * @return array
     */
    public function getLanguages()
    {
        return isset($this->fields['language_display_mv'])
            ? $this->fields['language_display_mv'] : [];
    }

    /**
     * Get human readable publication dates for display purposes (may not be suitable
     * for computer processing -- use getPublicationDates() for that).
     *
     * @return array
     */
    public function getHumanReadablePublicationDates()
    {
        $dc = $this->parseXML();
        $value = $dc->xpath('//dc:date');
        $ret = [];
        foreach ($value as $part) {
            $ret[] = (string) $part;
        }
        return empty($value) ? [] : $ret;
    }

    /**
     * Get the edition of the current record.
     *
     * @return string
     */
    public function getEdition()
    {
        return $this->fields['edition'] ?? '';
    }

    /**
     * Get all record links related to the current record. Each link is returned as
     * array.
     * Format:
     * array(
     *        array(
     *               'title' => label_for_title
     *               'value' => link_name
     *               'link'  => link_URI
     *        ),
     *        ...
     * )
     *
     * @return null|array
     */
    public function getAllRecordLinks()
    {
        // Load configurations:
        $fieldsNames = isset($this->mainConfig->Record->marc_links)
            ? explode(',', $this->mainConfig->Record->marc_links) : [];
        $useVisibilityIndicator
            = isset($this->mainConfig->Record->marc_links_use_visibility_indicator)
            ? $this->mainConfig->Record->marc_links_use_visibility_indicator : true;
        $retVal = [];
        foreach ($fieldsNames as $value) {
            $value = trim($value);
            $fields = $this->getMarcRecord()->getFields($value);
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    // Check to see if we should display at all
                    if ($useVisibilityIndicator) {
                        $visibilityIndicator = $field->getIndicator('1');
                        if ($visibilityIndicator == '1') {
                            continue;
                        }
                    }
                    // Get data for field
                    $tmp = $this->getFieldData($field);
                    if (is_array($tmp)) {
                        $retVal[] = $tmp;
                    }
                }
            }
        }
        return empty($retVal) ? null : $retVal;
    }

    public function getParentRecordID()
    {
        return $this->fields['parent_id_str'] ?? '';
    }

    /**
     * Get general notes on the record.
     *
     * @return array
     */
    public function getGeneralNotes()
    {
        $dc = $this->parseXML();
        $value = $dc->xpath('//dc:description');
        $ret = [];
        foreach ($value as $part) {
            $ret[] = (string) $part;
        }
        return empty($value) ? [] : $ret;
    }

    /**
     * Get the first call number associated with the record (empty string if none).
     *
     * @return string
     */
    public function getCallNumber()
    {
        $all = $this->getCallNumbers();
        return $all[0] ?? '';
    }
    /**
     * Get all call numbers associated with the record (empty string if none).
     *
     * @return array
     */
    public function getCallNumbers()
    {
        $dc = $this->parseXML();
        $value = $dc->xpath('//dc:identifier');
        $ret = [];
        foreach ($value as $part) {
            if (! is_int(strpos((string) $part, "signature:"))) continue;
            $ret[] = str_replace("signature:", "", (string) $part);
        }
        return empty($value) ? [] : $ret;
    }

    protected function parseXML()
    {
        if ( !isset($this->xmlCache)) {
            $fullrecord     = $this->getXML('oai_dc');
            $this->xmlCache = simplexml_load_string($fullrecord);
        }
        return $this->xmlCache;
    }
}

