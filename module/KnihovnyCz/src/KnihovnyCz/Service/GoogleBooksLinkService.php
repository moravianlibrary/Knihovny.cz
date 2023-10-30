<?php

namespace KnihovnyCz\Service;

use KnihovnyCz\RecordDriver\SolrDefault;

/**
 * Class GoogleBooksLinkService
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GoogleBooksLinkService extends LinkServiceAbstractBase
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
        $lccn = $record->getLCCN();
        $oclc = $record->getCleanOCLCNum();

        if (empty($isbn) && empty($lccn) && empty($oclc)) {
            return null;
        }

        $url = 'https://www.googleapis.com/books/v1/volumes';

        $params = [];
        if (!empty($isbn)) {
            $params = ['q' => 'isbn:' . str_replace('-', '', $isbn)];
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
            $canonicalLink
                = $data['items'][0]['volumeInfo']['canonicalVolumeLink'] ?? null;
            $link = isset($canonicalLink) ? $canonicalLink . '&sitesec=buy' : null;
        }

        return $link;
    }
}
