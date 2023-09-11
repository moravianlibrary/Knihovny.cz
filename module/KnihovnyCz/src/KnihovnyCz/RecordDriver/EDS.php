<?php

/**
 * Knihovny.cz EDS record driver
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
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */

namespace KnihovnyCz\RecordDriver;

use KnihovnyCz\RecordDriver\Feature\WikidataTrait;
use VuFind\Cache\CacheTrait;
use VuFind\View\Helper\Root\RecordLinker;

/**
 * Knihovny.cz EDS record driver
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class EDS extends \VuFind\RecordDriver\EDS
{
    use WikidataTrait;
    use CacheTrait;

    /**
     * Get access url
     *
     * @return string|null
     */
    public function getAccessUrl()
    {
        $items = $this->getItems(null, 'Access URL', 'URL');
        if (empty($items)) {
            return null;
        }
        $item = current($items);
        $url = strip_tags(html_entity_decode($item['Data']));
        $result = filter_var($url, FILTER_VALIDATE_URL);
        return ($result) ? $result : null;
    }

    /**
     * Get fulltext links
     *
     * @param RecordLinker $linker Record linker helper (optional; may be used to
     *                             inject record URLs into XML when appropriate).
     *
     * @return array
     */
    public function getFullTextLinks($linker)
    {
        $links = [];
        // Linked Full Text
        if (($link = $this->getLinkedFullTextLink()) != null) {
            $links['Linked Full Text'] = $link;
        } elseif ($this->hasLinkedFullTextAvailable()) {
            $links['Linked Full Text'] = $linker->getTabUrl($this, 'LinkedText');
        }
        // PDF Full Text
        if (($link = $this->getPdfLink()) != null) {
            $links['PDF Full Text'] = $link;
        } elseif ($this->hasPdfAvailable()) {
            $links['PDF Full Text'] = $linker->getTabUrl($this, 'PDF');
        }
        // ePub Full Text
        if (($link = $this->getEpubLink()) != null) {
            $links['ePub Full Text'] = $link;
        } elseif ($this->hasEpubAvailable()) {
            $links['ePub Full Text'] = $linker->getTabUrl($this, 'Epub');
        }
        // HTML Full Text
        if ($this->hasHTMLFullTextAvailable()) {
            $links['HTML Full Text'] = $linker->getUrl($this) . '#html';
        }
        return $links;
    }

    /**
     * Get the full text custom links of the record.
     *
     * @return array
     */
    public function getFTCustomLinks()
    {
        return [];
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
        $id = str_replace(['.', ','], '_', $this->getUniqueID());
        return 'edsrecord_' . $id . '_' . $suffix;
    }
}
