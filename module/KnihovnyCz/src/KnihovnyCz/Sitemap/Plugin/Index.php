<?php
/**
 * Index-based generator plugin
 *
 * PHP version 7
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
 * @category KnihovnyCz
 * @package  Sitemap
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
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
