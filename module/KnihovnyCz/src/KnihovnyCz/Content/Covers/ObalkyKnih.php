<?php

/**
 * Class ObalkyKnih
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
 * @package  KnihovnyCz\Content\Covers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Content\Covers;

use KnihovnyCz\Content\ObalkyKnihService;

/**
 * Class ObalkyKnih
 *
 * @category VuFind
 * @package  KnihovnyCz\Content\Covers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ObalkyKnih extends \VuFind\Content\Covers\ObalkyKnih
{
    /**
     * Obalky knih service
     *
     * @var ObalkyKnihService
     */
    protected $service;

    /**
     * Get image URL for a particular API key and set of IDs (or false if invalid).
     *
     * @param string $key  API key
     * @param string $size Size of image to load (small/medium/large)
     * @param array  $ids  Associative array of identifiers (keys may include 'isbn'
     * pointing to an ISBN object and 'issn' pointing to a string)
     *
     * @return string|bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getUrl($key, $size, $ids)
    {
        if (isset($ids['nbn']) && substr($ids['recordid'] ?? '', 0, 4) === 'auth') {
            return $this->getAuthorityImageUrl($ids['nbn'], $size);
        } else {
            return parent::getUrl($key, $size, $ids);
        }
    }

    /**
     * Get image url for given authority
     *
     * @param string $authId Authority record identifier
     * @param string $size   Desired size of cover
     *
     * @return string|false
     */
    protected function getAuthorityImageUrl($authId, $size)
    {
        $data = $this->service->getAuthorityData($authId);
        if (!isset($data)) {
            return false;
        }
        switch ($size) {
        case 'small':
            $imageUrl = $data->cover_icon_url ?? false; // @phpstan-ignore-line
            break;
        case 'medium':
            $imageUrl = $data->cover_medium_url ?? false; // @phpstan-ignore-line
            break;
        case 'large':
            $imageUrl = $data->cover_preview510_url ?? false; // @phpstan-ignore-line
            break;
        default:
            $imageUrl = $data->cover_medium_url ?? false; // @phpstan-ignore-line
            break;
        }
        return $imageUrl;
    }
}
