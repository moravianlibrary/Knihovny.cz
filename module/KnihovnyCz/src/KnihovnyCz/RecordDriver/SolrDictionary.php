<?php
/**
 * Knihovny.cz solr dictionary record driver
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

class SolrDictionary extends \KnihovnyCz\RecordDriver\SolrMarc
{
    /**
     * Get explanation.
     *
     * @return array $field
     */
    public function getSummary()
    {
        return isset ($this->fields ['explanation_display'])
            ? array($this->fields ['explanation_display']) : [];
    }

    /**
     * Get name, shown as title of record.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->fields ['title'] ?? [];
    }

    /**
     * Get english term.
     *
     * @return string
     */
    public function getEnglish()
    {
        return $this->fields ['english_display'] ?? [];
    }

    /**
     * Get explanation.
     *
     * @return string
     */
    public function getExplanation()
    {
        return $this->fields ['explanation_display'] ?? [];
    }

    /**
     * Get relative terms.
     *
     * @return array
     */
    public function getRelatives()
    {
        return $this->fields ['relative_display_mv'] ?? [];
    }
    /**
     * Get alternative terms.
     *
     * @return array
     */
    public function getAlternatives()
    {
        return $this->fields ['alternative_display_mv'] ?? [];
    }
    /**
     * Get source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->fields ['source_display'] ?? [];
    }

    /**
     * Get handler for related
     *
     * @return array
     */
    public function getFilterParamsForRelated()
    {
        return ['handler' => 'morelikethisdictionary'];
    }




}

