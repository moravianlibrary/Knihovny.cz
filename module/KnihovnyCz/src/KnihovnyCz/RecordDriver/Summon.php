<?php
declare(strict_types=1);

/**
 * Class Summon
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2022.
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\RecordDriver;

use KnihovnyCz\RecordDriver\Feature\WikidataTrait;
use VuFind\Cache\CacheTrait;
use VuFind\View\Helper\Root\RecordLinker;

/**
 * Class Summon
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Summon extends \VuFind\RecordDriver\Summon
{
    use WikidataTrait;
    use CacheTrait;

    /**
     * Get fulltext links
     *
     * @param RecordLinker $linker Record linker helper (optional; may be used to
     * inject record URLs into XML when appropriate).
     *
     * @return array
     */
    public function getFullTextLinks(RecordLinker $linker)
    {
        $links = [];
        if (isset($this->fields['OpenAccessLink'])) {
            $links['Open access link'] = $this->fixOpenAccessLink(
                $this->fields['OpenAccessLink'][0]
            );
        }
        return $links;
    }

    /**
     * Checks the current record if it's supported for generating OpenURLs.
     *
     * @return bool
     */
    public function supportsOpenUrl()
    {
        return true;
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
        return 'summonrecord_' . $id . '_' . $suffix;
    }

    /**
     * Fix Open access link
     *
     * @param string $url Url to fix
     *
     * @return mixed
     */
    protected function fixOpenAccessLink($url)
    {
        if (str_contains($url, '%requestingapplication%')) {
            return str_replace(
                '%requestingapplication%',
                'summon',
                $url
            );
        }
        return $url;
    }
}
