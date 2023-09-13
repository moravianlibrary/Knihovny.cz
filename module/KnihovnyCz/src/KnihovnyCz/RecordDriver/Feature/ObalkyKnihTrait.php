<?php

/**
 * Trait ObalkyKnihTrait
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2019.
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

use VuFind\Content\ObalkyKnihService;
use VuFindCode\ISBN;
use VuFindCode\ISMN;

use function is_array;

/**
 * Trait ObalkyKnihTrait
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait ObalkyKnihTrait
{
    /**
     * ObalkyKnih.cz API client
     *
     * @var ObalkyKnihService
     */
    protected ObalkyKnihService $obalkyKnih;

    /**
     * Get TOC URL
     *
     * @return array|null
     */
    public function getObalkyKnihToc(): ?array
    {
        $toc = null;
        $ids = $this->getThumbnail();
        if (!is_array($ids)) {
            return null;
        }
        if (!empty($ids['isbns'])) {
            $ids['isbns'] = array_map(
                function ($isbn) {
                    return new ISBN($isbn);
                },
                $ids['isbns']
            );
            $ids['isbn'] = $ids['isbns'][0];
        }
        if (isset($ids['ismn'])) {
            $ids['ismn'] = new ISMN($ids['ismn']);
        }
        $data = $this->obalkyKnih->getData($ids);
        if (isset($data->toc_thumbnail_url)) {
            $toc = [
                'url' => $data->toc_pdf_url,
                'image' => $data->toc_thumbnail_url,
            ];
        }
        return $toc;
    }

    /**
     * Attach service for ObalkyKnih.cz
     *
     * @param ObalkyKnihService $obalkyService ObalkyKnih.cz API client
     *
     * @return void
     */
    public function attachObalkyKnihService(ObalkyKnihService $obalkyService): void
    {
        $this->obalkyKnih = $obalkyService;
    }
}
