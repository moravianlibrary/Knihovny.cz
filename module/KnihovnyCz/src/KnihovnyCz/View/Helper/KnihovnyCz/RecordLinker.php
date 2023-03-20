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

use VuFind\RecordDriver\AbstractBase as BaseRecord;
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
     * Record loader
     *
     * @var \VuFind\Record\Loader
     */
    protected $recordLoader;

    /**
     * Constructor
     *
     * @param \VuFind\Record\Router  $router       Record router
     * @param \Laminas\Config\Config $searchConfig Search configuration
     * @param \VuFind\Record\Loader  $recordLoader Record loader
     */
    public function __construct(
        \VuFind\Record\Router $router,
        \Laminas\Config\Config $searchConfig,
        \VuFind\Record\Loader $recordLoader
    ) {
        parent::__construct($router);
        $this->searchConfig = $searchConfig;
        $this->recordLoader = $recordLoader;
    }

    /**
     * Return a link to main portal
     *
     * @param BaseRecord $driver Record driver
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

    /**
     * Given a record driver, get a URL for that record that links to local
     * record.
     *
     * @param BaseRecord|string $record      Record driver representing record to
     * link to, or source|id pipe-delimited string
     * @param string|null       $institution Institution
     *
     * @return string
     */
    public function getLinkToLocalRecord(
        BaseRecord|string $record,
        ?string $institution = null
    ): string {
        if (!$record instanceof BaseRecord) {
            $record = $this->loadRecord($record);
        }
        $recordId = $record->getUniqueID();

        $records = $record->tryMethod('getDeduplicatedRecords');
        if (!empty($records)) {
            $first = reset($records);
            if ($institution !== null && isset($records[$institution])) {
                $first = $records[$institution];
            }
            $recordId = reset($first);
        }

        return $this->getUrl($recordId);
    }

    /**
     * Load record by given id.
     *
     * @param string $recordId Record id
     *
     * @return BaseRecord
     */
    protected function loadRecord(string $recordId)
    {
        [$sourceId, $recordId] = explode('|', $recordId);
        return $this->recordLoader->load($recordId, $sourceId);
    }
}
