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

use VuFind\View\Helper\Root\RecordLink;

class SolrDublinCore extends SolrDefault
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
            if (preg_match('/^([\W0-9]+|neuvedeno|n[ae]zadáno)$/', $part)) {
                continue;
            }
            $ret[] = [(string) $part];
        }
        return $ret;
    }

    /**
     * Get the full title of the record.
     *
     * @return string
     */
    public function getTitle()
    {
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
     * Get an array of all the languages associated with the record.
     *
     * @return array
     */
    public function getLanguages()
    {
        return $this->fields['language_display_mv'] ?? [];
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
        return ($value === false) ? [] : array_map('strval', $value);
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
        return ($value === false) ? [] : array_map('strval', $value);
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
            if (!is_int(strpos((string) $part, "signature:"))) {
                continue;
            }
            $ret[] = str_replace("signature:", "", (string) $part);
        }
        return $ret;
    }

    /**
     * Return an XML representation of the record using the specified format.
     * Return false if the format is unsupported.
     *
     * @param string     $format     Name of format to use (corresponds with OAI-PMH
     * metadataPrefix parameter).
     * @param string     $baseUrl    Base URL of host containing VuFind (optional;
     * may be used to inject record URLs into XML when appropriate).
     * @param RecordLink $recordLink Record link helper (optional; may be used to
     * inject record URLs into XML when appropriate).
     *
     * @return mixed         XML, or false if format unsupported.
     */
    public function getXML($format, $baseUrl = null, $recordLink = null)
    {
        // We have oai_dc xml saved in fullrecord field in solr, no need to create it
        return ($format === 'oai_dc') ? $this->fields['fullrecord']
            : parent::getXML($format, $baseUrl, $recordLink);
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
