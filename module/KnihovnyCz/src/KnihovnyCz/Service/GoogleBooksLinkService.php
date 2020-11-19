<?php

/**
 * Class GoogleBooksLinkService
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

class GoogleBooksLinkService extends LinkServiceAbstractBase
{
    /**
     * Get link to record representation on given service
     *
     * @param SolrDefault $record
     *
     * @return string|null
     */
    public function getLink(SolrDefault $record): ?string
    {
        $isbns = $record->getISBNs();
        $isbn = $isbns[0] ?? null;
        $lccn = $record->getLCCN();
        $oclc = $record->getCleanOCLCNum();

        if (empty($isbn) && empty($lccn) && empty($oclc)) {
            return null;
        }

        $url = 'https://www.googleapis.com/books/v1/volumes';

        $params = [];
        if (!empty($isbn)) {
            $params = ['q' => 'isbn:' . str_replace("-", "", $isbn)];
        }
        if (!empty($lccn)) {
            $params = ['q' => 'lccn:' . $lccn];
        }
        if (!empty($oclc)) {
            $params = ['q' => 'oclc:' . $oclc];
        }

        $data = $this->getDataFromService($url, $params);

        $link = null;
        if (!empty($data) && $data['totalItems'] >= 1) {
            $canonicalLink = $data['items'][0]['volumeInfo']['canonicalVolumeLink'] ?? null;
            $link = isset($canonicalLink) ? $canonicalLink . '&sitesec=buy' : null;
        }

        return $link;
    }
}
