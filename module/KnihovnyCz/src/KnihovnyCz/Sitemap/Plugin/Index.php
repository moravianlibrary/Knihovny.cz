<?php

namespace KnihovnyCz\Sitemap\Plugin;

use VuFind\Sitemap\Plugin\Index\AbstractIdFetcher;
use VuFind\Sitemap\Plugin\Index as Base;

/**
 * Index-based generator plugin
 *
 * @category KnihovnyCz
 * @package  Sitemap
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class Index extends Base
{
    /**
     * Record tab
     *
     * @var string
     */
    protected $tab  = '';

    /**
     * Constructor
     *
     * @param array             $backendSettings Settings specifying which
     *                                           backends to index
     * @param AbstractIdFetcher $idFetcher       The helper object for
     *                                           retrieving IDs
     * @param int               $countPerPage    Page size for data retrieval
     * @param string[]          $filters         Search filters
     * @param string            $tab             Record tab
     */
    public function __construct(
        array $backendSettings,
        AbstractIdFetcher $idFetcher,
        int $countPerPage,
        array $filters = [],
        string $tab = ''
    ) {
        parent::__construct($backendSettings, $idFetcher, $countPerPage, $filters);
        $this->tab = $tab;
    }

    /**
     * Generate urls for the sitemap.
     *
     * @return \Generator
     */
    public function getUrls(): \Generator
    {
        $suffix = '';
        if (!empty($this->tab)) {
            $suffix = '/' . $this->tab;
        }
        foreach (parent::getUrls() as $url) {
            yield $url . $suffix;
        }
    }
}
