<?php

/**
 * Class ObalkyKnih
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
 * @package  KnihovnyCz\Content\TOC
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Content\TOC;

/**
 * Class ObalkyKnih
 *
 * @category VuFind
 * @package  KnihovnyCz\Content\TOC
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ObalkyKnih extends \VuFind\Content\AbstractBase
{
    /**
     * Obalky knih service
     *
     * @var \VuFind\Content\ObalkyKnihService
     */
    protected $service;

    /**
     * Constructor
     *
     * @param \VuFind\Content\ObalkyKnihService $service ObalkyKnih.cz API client
     */
    public function __construct($service)
    {
        $this->service = $service;
    }

    /**
     * This method is responsible for generating fake TOC data for testing
     * purposes.
     *
     * @param string           $key     API key
     * @param \VuFindCode\ISBN $isbnObj ISBN object
     *
     * @throws \Exception
     * @return array     Returns array with table of contents data.
     */
    public function loadByIsbn($key, \VuFindCode\ISBN $isbnObj)
    {
        $ids = [
            'isbn' => $isbnObj,
        ];
        $data = $this->service->getData($ids);
        $toc = [];
        if (isset($data->toc_thumbnail_url)) {
            $toc[] = "<br><a href='" . htmlspecialchars($data->toc_pdf_url)
                . "' target='_blank' ><img src='"
                . htmlspecialchars($data->toc_thumbnail_url) . "'></a>";
        }
        return $toc;
    }
}
