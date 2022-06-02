<?php

/**
 * Solr deduplication listener for views.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace KnihovnyCz\Search\Solr;

use Laminas\EventManager\EventInterface;
use Psr\Container\ContainerInterface;
use VuFindSearch\Backend\Solr\Backend;

/**
 * Solr deduplication listener for views.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class ChildDocDeduplicationListener extends DeduplicationListener
{
    public const CHILD_DOCUMENT_LIMIT = 10000;

    /**
     * Record factory
     *
     * @var RecordFactory
     */
    protected $recordFactory;

    /**
     * Field list to fetch from Solr
     *
     * @var string
     */
    protected $fieldList;

    /**
     * Constructor.
     *
     * @param Backend            $backend        Search backend
     * @param ContainerInterface $serviceLocator Service locator
     * @param string             $searchCfg      Search config file id
     * @param string             $facetCfg       Facet config file id
     * @param string             $dataSourceCfg  Data source file id
     * @param bool               $enabled        Whether deduplication is
     * enabled
     */
    public function __construct(
        Backend $backend,
        ContainerInterface $serviceLocator,
        $searchCfg,
        $facetCfg,
        $dataSourceCfg = 'datasources',
        $enabled = true
    ) {
        parent::__construct(
            $backend,
            $serviceLocator,
            $searchCfg,
            $facetCfg,
            $dataSourceCfg,
            $enabled
        );
        $this->recordFactory = $this->serviceLocator
            ->get('VuFind\RecordDriverPluginManager');
        $this->fieldList = $this->getListOfFields();
    }

    /**
     * Set up filter for excluding merged children.
     *
     * @param EventInterface $event Event
     *
     * @return EventInterface
     */
    public function onSearchPre(EventInterface $event)
    {
        if (!$this->enabled) {
            return $event;
        }
        $command = $event->getParam('command');
        if ($command->getTargetIdentifier() === $this->backend->getIdentifier()) {
            $params = $command->getSearchParameters();
            $context = $command->getContext();
            if ($context == 'search' || $context == 'similar') {
                $this->configureFilter($params);
            }
        }
        return $event;
    }

    /**
     * Get filter for limiting results
     *
     * @param \VuFindSearch\ParamBag $params Search parameters
     *
     * @return void
     */
    protected function configureFilter($params)
    {
        $applyChildFilter = true;
        if ($params instanceof \KnihovnyCz\Search\ParamBag) {
            $applyChildFilter = $params->isApplyChildFilter();
        }
        $config = $this->serviceLocator->get('VuFind\Config');
        $searchConfig = $config->get($this->searchConfig);
        $childFilters = [];
        if ($applyChildFilter && isset($searchConfig->ChildRecordFilters)) {
            $childFilters = $searchConfig->ChildRecordFilters
                ->toArray();
        }
        foreach ($childFilters as $filter) {
            $params->add('fq', $filter);
        }
        if ($this->hasChildFilter($params)) {
            return;
        }
        $params->set('uniqueId', 'local_ids_str_mv');
        $params->add('fq', '-merged_child_boolean:true');
        $config = $this->serviceLocator->get('VuFind\Config');
        if (isset($searchConfig->RawHiddenFilters)) {
            $childFilters = array_merge(
                $childFilters,
                $searchConfig->RawHiddenFilters->toArray()
            );
        }
        $childFilter = '';
        if (!empty($childFilters)) {
            $childFilter = 'childFilter=\'' . join(
                " AND ",
                $childFilters
            ) . '\'';
        }
        $fl = $params->get('fl');
        if (empty($fl)) {
            $fl = $this->fieldList;
        }
        $limit = self::CHILD_DOCUMENT_LIMIT;
        $fl = $fl . ", [child parentFilter=merged_boolean:true"
            . " $childFilter limit=$limit]";
        $params->set('fl', $fl);
    }

    /**
     * Fetch local records for all the found dedup records
     *
     * @param EventInterface $event Event
     *
     * @return void
     */
    protected function fetchLocalRecords($event)
    {
        $command = $event->getParam('command');
        $result = $command->getResult();
        foreach ($result->getRecords() as $record) {
            $fields = $record->getRawData();
            $fields['local_ids_str_mv'] = $this->getLocalRecordIds($fields);
            $record->setRawData($fields);
        }
        parent::fetchLocalRecords($event);
    }

    /**
     * Return local record ids
     *
     * @param array $fields fields
     *
     * @return array
     */
    protected function getLocalRecordIds($fields)
    {
        $ids = [];
        $childs = $fields['_childDocuments_'] ?? [];
        foreach ($childs as $rawLocalRecord) {
            $ids[] = $rawLocalRecord['id'];
        }
        return $ids;
    }

    /**
     * Append fields from dedup record to the selected local record. Note: the last
     * two parameters are unused in this default method, but they may be useful for
     * custom behavior in subclasses.
     *
     * @param array $localRecordData Local record data
     * @param array $dedupRecordData Dedup record data
     * @param array $recordSources   List of active record sources, empty if all
     * @param array $sourcePriority  Array of source priorities keyed by source id
     *
     * @return array Local record data
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function appendDedupRecordFields(
        $localRecordData,
        $dedupRecordData,
        $recordSources,
        $sourcePriority
    ) {
        return $localRecordData;
    }

    /**
     * Return list of fields to fetch from Solr
     *
     * @return string|null
     */
    protected function getListOfFields()
    {
        $config = $this->serviceLocator->get(
            \VuFind\Config\PluginManager::class
        );
        $searchConfig = $config->get($this->searchConfig);
        $search = $searchConfig->get($this->searchConfig);
        if (isset($search->Solr->default_field_list_mode)) {
            $mode = $search->Solr->default_field_list_mode;
            if ($mode == 'solr') {
                return null;
            } elseif ($mode == 'override'
                && isset($search->Solr->default_field_list_override)
            ) {
                return $search->Solr->default_field_list_override;
            }
        }
        return '*,score';
    }
}
