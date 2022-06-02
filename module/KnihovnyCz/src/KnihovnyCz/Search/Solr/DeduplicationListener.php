<?php
declare(strict_types=1);

/**
 * Solr deduplication (merged records) listener.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace KnihovnyCz\Search\Solr;

use Laminas\EventManager\EventInterface;
use Psr\Container\ContainerInterface;
use VuFind\Search\Solr\DeduplicationListener as ParentDeduplicationListener;
use VuFindSearch\Backend\Solr\Backend;
use VuFindSearch\ParamBag;

/**
 * Solr merged record handling listener.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class DeduplicationListener extends ParentDeduplicationListener
{
    public const OR_FACETS_REGEX = '/(\\{[^\\}]*\\})*([\S]+):\\((.+)\\)/';

    public const FILTER_REGEX = '/(\S+):"([^"]+)"/';

    public const UNDEF_PRIORITY = 99999;

    public const MIN_PRIORITY = 999999;

    /**
     * Facet configuration file id
     *
     * @var string
     */
    protected $facetConfig;

    /**
     * Auth Manager
     *
     * @var \VuFind\Auth\Manager
     */
    protected $authManager;

    /**
     * Solr institution field
     *
     * @var string
     */
    protected $institutionField = 'region_institution_facet_mv';

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
            $dataSourceCfg,
            $enabled
        );
        $this->facetConfig = $facetCfg;
        $config = $this->serviceLocator->get(\VuFind\Config\PluginManager::class);
        $this->authManager = $this->serviceLocator->get('VuFind\AuthManager');
        $searchConfig = $config->get($this->searchConfig);
        if (isset($searchConfig->Records->institution_field)) {
            $this->institutionField = $searchConfig->Records->institution_field;
        }
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
        $params = $command->getSearchParameters();
        $sourcePriority = $this->determineRecordPriority($params);

        $idList = [];
        // Find out the best records and list their IDs:
        /**
         * Result
         *
         * @var \VuFindSearch\Backend\Solr\Response\Json\RecordCollection
         */
        $result = $command->getResult();
        foreach ($result->getRecords() as $record) {
            $fields = $record->getRawData();

            if (!isset($fields['merged_boolean'])) {
                continue;
            }
            $localIds = $fields['local_ids_str_mv'];
            $undefPriority = self::UNDEF_PRIORITY;
            // Find the document that matches the source priority best:
            $dedupData = [];
            foreach ($localIds as $localId) {
                $localPriority = null;
                [$source] = explode('.', $localId, 2);
                if (isset($sourcePriority[$source])) {
                    $localPriority = $sourcePriority[$source];
                } else {
                    $localPriority = ++$undefPriority;
                }
                $dedupData[$source] = [
                    'id' => $localId,
                    'priority' => $localPriority,
                ];
            }

            // Sort dedupData by priority:
            uasort(
                $dedupData,
                function ($a, $b) {
                    return $a['priority'] - $b['priority'];
                }
            );

            $firstDedupRecord = reset($dedupData);
            if ($firstDedupRecord !== false) {
                $fields['dedup_data'] = $dedupData;
                $dedupId = $firstDedupRecord['id'];
                $fields['dedup_id'] = $dedupId;
                $record->setRawData($fields);
                $idList[] = $dedupId;
            }
        }
        if (empty($idList)) {
            return;
        }

        // Fetch records and assign them to the result:
        $localRecords = $this->backend->retrieveBatch($idList)->getRecords();
        foreach ($result->getRecords() as $record) {
            $dedupRecordData = $record->getRawData();
            if (!isset($dedupRecordData['dedup_id'])) {
                continue;
            }
            // Find the corresponding local record in the results:
            $foundLocalRecord = null;
            foreach ($localRecords as $localRecord) {
                if ($localRecord->getUniqueID() == $dedupRecordData['dedup_id']) {
                    $foundLocalRecord = $localRecord;
                    break;
                }
            }
            if (!$foundLocalRecord) {
                continue;
            }

            $localRecordData = $foundLocalRecord->getRawData();

            // Copy dedup_data for the active data sources:
            foreach ($dedupRecordData['dedup_data'] as $dedupDataKey => $dedupData) {
                $localRecordData['dedup_data'][$dedupDataKey] = $dedupData;
            }

            // Copy fields from dedup record to local record
            $localRecordData = $this->appendDedupRecordFields(
                $localRecordData,
                $dedupRecordData,
                [],
                $sourcePriority
            );
            $foundLocalRecord->setRawData($localRecordData);
            $foundLocalRecord->setHighlightDetails($record->getHighlightDetails());
            $result->replace($record, $foundLocalRecord);
        }
    }

    /**
     * Function that determines the priority for sources
     *
     * @param ParamBag $params Parameters
     *
     * @return array Array keyed by source with priority as the value
     */
    protected function determineRecordPriority($params)
    {
        $cards = $this->getSourcesFromLibraryCards();
        $filters = $this->getSourcesFromFilters($params);
        $common = array_intersect($cards, $filters);
        $sources = array_unique(array_merge($common, $filters, $cards));
        $sourcePriority = [];
        $index = 0;
        foreach ($sources as $source) {
            $sourcePriority[$source] = $index++;
        }
        $index = self::MIN_PRIORITY;
        foreach ($this->getNonPreferredSources() as $source) {
            if (!array_key_exists($source, $sourcePriority)) {
                $sourcePriority[$source] = $index++;
            }
        }
        return $sourcePriority;
    }

    /**
     * Get institutions from user's library cards
     *
     * @return array
     */
    public function getSourcesFromLibraryCards()
    {
        /**
         * User model
         *
         * @var \KnihovnyCz\Db\Row\User|false $user
         */
        $user = $this->authManager->isLoggedIn();
        if (!$user) {
            return [];
        }
        return $user->getLibraryPrefixes();
    }

    /**
     * Get sources from facet filters
     *
     * @param ParamBag $params parameters
     *
     * @return array preferred institutions from user library cards
     */
    public function getSourcesFromFilters($params)
    {
        $config = $this->serviceLocator->get(\VuFind\Config\PluginManager::class);
        $facetConfig = $config->get($this->facetConfig);
        if (!isset($facetConfig->InstitutionsMappings)) {
            return [];
        }
        $institutionMappings = [];
        foreach ($facetConfig->InstitutionsMappings as $source => $filter) {
            $index = 0;
            $path = '';
            $elements = array_slice(explode('/', $filter), 1);
            foreach ($elements as $element) {
                if (empty($element)) {
                    break;
                }
                $path .= '/' . $element;
                $institutionMappings[$index . $path . '/'][] = $source;
                $index++;
            }
        }
        $values = [];
        foreach ($params->get('fq') as $fq) {
            if (preg_match(self::OR_FACETS_REGEX, $fq, $matches)) {
                $field = $matches[2];
                if ($field != $this->institutionField) {
                    continue;
                }
                $filters = explode('OR', $matches[3]);
                foreach ($filters as $filter) {
                    if (preg_match(self::FILTER_REGEX, $filter, $matches)) {
                        $values[] = $matches[2];
                    }
                }
            } elseif (preg_match(self::FILTER_REGEX, $fq, $matches)) {
                $field = $matches[1];
                if ($field != $this->institutionField) {
                    continue;
                }
                $values[] = $matches[2];
            }
        }
        $priorities = [];
        foreach ($values as $value) {
            $prefixes = $institutionMappings[$value] ?? null;
            if ($prefixes) {
                array_push($priorities, ...$prefixes);
            }
        }
        return $priorities;
    }

    /**
     * Get non preferred sources
     *
     * @return array
     */
    public function getNonPreferredSources()
    {
        $config = $this->serviceLocator->get(\VuFind\Config\PluginManager::class);
        $searchConfig = $config->get($this->searchConfig);
        if (empty($searchConfig->Records->nonPreferredSources)) {
            return [];
        }
        return explode(',', $searchConfig->Records->nonPreferredSources);
    }
}
