<?php
/**
 * Record loader
 *
 * PHP version 7
 *
 * Copyright (C) The Moravian Library 2022.
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
 * @package  Record
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
namespace KnihovnyCz\Record;

use VuFind\Record\Cache;
use VuFind\Record\FallbackLoader\PluginManager as FallbackLoader;
use VuFind\Record\Loader as LoaderBase;
use VuFind\RecordDriver\PluginManager as RecordFactory;
use VuFindSearch\Command\SearchCommand;
use VuFindSearch\ParamBag;
use VuFindSearch\Service as SearchService;

/**
 * Record loader
 *
 * @category VuFind
 * @package  Record
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class Loader extends LoaderBase
{
    /**
     * Max records to fetch
     *
     * @var int
     */
    public const MAX_RECORDS = 10000;

    /**
     * Filter child records
     *
     * @var bool
     */
    protected $filterChildRecords = false;

    /**
     * Constructor
     *
     * @param SearchService  $searchService      Search service
     * @param RecordFactory  $recordFactory      Record loader
     * @param Cache          $recordCache        Record Cache
     * @param FallbackLoader $fallbackLoader     Fallback record loader
     * @param bool           $filterChildRecords Filter child records
     */
    public function __construct(
        SearchService $searchService,
        RecordFactory $recordFactory,
        Cache $recordCache = null,
        FallbackLoader $fallbackLoader = null,
        bool $filterChildRecords = true
    ) {
        $this->searchService = $searchService;
        $this->recordFactory = $recordFactory;
        $this->recordCache = $recordCache;
        $this->fallbackLoader = $fallbackLoader;
        $this->filterChildRecords = $filterChildRecords;
    }

    /**
     * Given an ID and record source, load the requested record object.
     *
     * @param string   $id              Record ID
     * @param string   $source          Record source
     * @param bool     $tolerateMissing Should we load a "Missing" placeholder
     * instead of throwing an exception if the record cannot be found?
     * @param ParamBag $params          Search backend parameters
     *
     * @throws \Exception
     * @return \VuFind\RecordDriver\AbstractBase
     */
    public function load(
        $id,
        $source = DEFAULT_SEARCH_BACKEND,
        $tolerateMissing = false,
        ParamBag $params = null
    ) {
        $record = parent::load($id, $source, $tolerateMissing, $params);
        $fields = $record->getRawData();
        $parentRecord = $fields['merged_boolean'] ?? false;
        if ($parentRecord && $this->filterChildRecords) {
            $params = new \KnihovnyCz\Search\ParamBag(
                [
                    'fq' => ['merged_child_boolean:true'],
                    'fl' => 'id',
                    'hl' => ['false'],
                ]
            );
            $params->setMultiplyingDeduplicationListener(false);
            $query = new \VuFindSearch\Query\Query('_root_' . ':' . $id);
            $command = new SearchCommand(
                'Solr',
                $query,
                0,
                self::MAX_RECORDS,
                $params
            );
            $childIds = [];
            $results = $this->searchService->invoke($command)->getResult();
            foreach ($results->getRecords() as $record) {
                $data = $record->getRawData();
                $childIds[] = $data['id'];
            }
            $fields['local_ids_str_mv']  = $childIds;
            $record->setRawData($fields);
        }
        return $record;
    }
}
