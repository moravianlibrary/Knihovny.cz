<?php

/**
 * Class Links
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
 * @package  KnihovnyCz\Related
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Related;

use KnihovnyCz\RecordDriver\SolrDefault;

/**
 * Class Links
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Related
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Links implements \VuFind\Related\RelatedInterface
{
    /**
     * Links
     *
     * @var array
     */
    protected array $links;

    /**
     * Module label
     *
     * @var string
     */
    protected string $label;

    /**
     * Establishes base settings for making recommendations.
     *
     * @param string      $settings Settings from config.ini
     * @param SolrDefault $driver   Record driver object
     *
     * @return void
     */
    public function init($settings, $driver)
    {
        $this->links = match ($settings) {
            'external' => $driver->tryMethod('getExternalLinks') ?? [],
            'identifiers' => $driver->tryMethod('getIdentifiersLinks') ?? [],
            'socialsites' => $driver->tryMethod('getSocialSitesLinks') ?? [],
            default => [],
        };
        $this->label = "related_" . $settings . "_links";
    }

    /**
     * Get an array of external links
     *
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Get label for related module
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }
}
