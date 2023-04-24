<?php

/**
 * Trait BuyLinksTrait
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
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
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\RecordDriver\Feature;

use KnihovnyCz\Service\LinkServiceInterface;

/**
 * Trait BuyLinksTrait
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait BuyLinksTrait
{
    /**
     * Service for getting link Google Books
     *
     * @var LinkServiceInterface
     */
    protected LinkServiceInterface $googlebooksService;

    /**
     * Service for getting link to Zboží.cz
     *
     * @var LinkServiceInterface
     */
    protected LinkServiceInterface $zboziService;

    /**
     * Get buy links configuration
     *
     * @return array Array of array with keys: key, method, label
     */
    protected function getBuyLinksConfiguration(): array
    {
        return [
            [
                'key' => 'antikvariaty',
                'method' => 'getAntikvariatyLink',
                'label' => 'Antikvariát',
            ],
            [
                'key' => 'zbozi',
                'method' => 'getZboziLink',
                'label' => 'Zboží.cz',
            ],
            [
                'key' => 'googlebooks',
                'method' => 'getGoogleBooksLink',
                'label' => 'Google Books',
            ],
        ];
    }

    /**
     * Get buy links
     *
     * @return array Array of array with keys: label, href
     */
    public function getBuyLinks(): array
    {
        $data = [];
        foreach ($this->getBuyLinksConfiguration() as $link) {
            $href = $this->{$link['method']}();
            if ($href !== null) {
                $data[$link['key']] = [
                    'label' => $link['label'],
                    'href' => $href,
                ];
            }
        }
        return $data;
    }

    /**
     * Check whether is there any buy link
     *
     * @return bool
     */
    public function hasBuyLinks(): bool
    {
        foreach ($this->getBuyLinksConfiguration() as $link) {
            try {
                if ($this->{$link['method']}()) {
                    return true;
                }
            } catch (\Exception $ex) {
                // ignore exception and consider as not available
            }
        }
        return false;
    }

    /**
     * Get link to muj-antikvariat.cz
     *
     * @return string|null
     */
    public function getAntikvariatyLink(): ?string
    {
        $link = $this->fields['external_links_str_mv'][0] ?? null;
        if ($link === null) {
            /**
             * Parent record
             *
             * @var \KnihovnyCz\RecordDriver\SolrDefault $parentRecord
             */
            $parentRecord = $this->getParentRecord();
            if ($parentRecord !== null) {
                return $parentRecord->getAntikvariatyLink();
            }
        }
        return $link;
    }

    /**
     * Get link to Zboží.cz
     *
     * @return string|null
     */
    public function getZboziLink()
    {
        return $this->zboziService->getLink($this);
    }

    /**
     * Get link to Google Books
     *
     * @return string|null
     */
    public function getGoogleBooksLink()
    {
        return $this->googlebooksService->getLink($this);
    }

    /**
     * Attach service for Google Books
     *
     * @param LinkServiceInterface $googleService Google books API client
     *
     * @return void
     */
    public function attachGoogleService(LinkServiceInterface $googleService): void
    {
        $this->googlebooksService = $googleService;
    }

    /**
     * Attach service for Zboží.cz
     *
     * @param LinkServiceInterface $zboziService Zbozi.cz API client
     *
     * @return void
     */
    public function attachZboziService(LinkServiceInterface $zboziService): void
    {
        $this->zboziService = $zboziService;
    }
}
