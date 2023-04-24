<?php

/**
 * Class ZboziService
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
 * @package  KnihovnyCz\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\Service;

use KnihovnyCz\RecordDriver\SolrDefault;

/**
 * Class ZboziService
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZboziLinkService extends LinkServiceAbstractBase
{
    /**
     * Get link to record representation on given service
     *
     * @param SolrDefault $record Record driver
     *
     * @return string|null
     */
    public function getLink(SolrDefault $record): ?string
    {
        $isbns = $record->getISBNs();
        $isbn = $isbns[0] ?? null;

        if (empty($isbn)) {
            return null;
        }

        $url = 'https://www.zbozi.cz/api/v1/search';
        $params = [
            'groupByCategory' => 0,
            'loadTopProducts' => 'true',
            'page' => 1,
            'query' => str_replace("-", "", $isbn),
        ];

        $data = $this->getDataFromService($url, $params);

        $link = null;

        if (!empty($data) && isset($data['status']) && $data['status'] === 200) {
            $productUrl = $data['products'][0]['normalizedName'] ?? null;
            if ($productUrl !== null) {
                $link = 'https://www.zbozi.cz/vyrobek/' . urlencode($productUrl)
                    . '/';
            }
        }
        return $link;
    }
}
