<?php

/**
 * RecordLinker
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
 * @category VuFind
 * @package  KnihovnyCz\View_Helpers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\View\Helper\KnihovnyCz;

use VuFind\View\Helper\Root\RecordLinker as Base;

/**
 * RecordLinker
 *
 * @category VuFind
 * @package  KnihovnyCz\View_Helpers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class RecordLinker extends Base
{
    /**
     * Search config
     *
     * @var \Laminas\Config\Config
     */
    protected $searchConfig;

    /**
     * Constructor
     *
     * @param \VuFind\Record\Router  $router       Record router
     * @param \Laminas\Config\Config $searchConfig Search configuration
     */
    public function __construct(
        \VuFind\Record\Router $router,
        \Laminas\Config\Config $searchConfig
    ) {
        parent::__construct($router);
        $this->searchConfig = $searchConfig;
    }

    /**
     * Return a link to main portal
     *
     * @param \VuFind\RecordDriver\AbstractBase $driver Record driver
     * representing record to link to
     *
     * @return string
     */
    public function getLinkToMainPortal($driver)
    {
        $baseUrl = $this->searchConfig->OtherPortals->main ?? null;
        if ($baseUrl == null) {
            return null;
        }
        return rtrim($baseUrl, '/') . $this->getTabUrl($driver);
    }
}
