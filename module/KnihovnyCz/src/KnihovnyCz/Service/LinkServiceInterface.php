<?php

namespace KnihovnyCz\Service;

use KnihovnyCz\RecordDriver\SolrDefault;

/**
 * Interface LinkServiceInterface
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
interface LinkServiceInterface
{
    /**
     * Get link to record representation on given service
     *
     * @param SolrDefault $record Record driver
     *
     * @return string|null
     */
    public function getLink(SolrDefault $record): ?string;
}
