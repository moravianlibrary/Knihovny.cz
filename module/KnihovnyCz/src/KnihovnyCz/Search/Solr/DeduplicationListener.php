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
 * @category VuFind2
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace KnihovnyCz\Search\Solr;

use Interop\Container\ContainerInterface;
use VuFind\Auth\Manager as AuthManager;
use VuFind\Search\Solr\DeduplicationListener as ParentDeduplicationListener;
use VuFindSearch\Backend\Solr\Backend;
use VuFindSearch\ParamBag;
use Zend\EventManager\EventInterface;

/**
 * Solr merged record handling listener.
 *
 * @category VuFind2
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class DeduplicationListener extends ParentDeduplicationListener
{
    const OR_FACETS_REGEX = '/(\\{[^\\}]*\\})*([\S]+):\\((.+)\\)/';

    const FILTER_REGEX = '/(\S+):"([^"]+)"/';

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
     * @param AuthManager        $authManager    AuthManager
     * @param string             $facetCfg       Facet config file id
     * @param string             $dataSourceCfg  Data source file id
     * @param bool               $enabled        Whether deduplication is
     * enabled
     */
    public function __construct(
        Backend $backend, ContainerInterface $serviceLocator,
        $searchCfg, $authManager, $facetCfg,
        $dataSourceCfg = 'datasources', $enabled = true
    ) {
        parent::__construct(
            $backend, $serviceLocator, $searchCfg,
            $dataSourceCfg, $enabled
        );
        $this->authManager = $authManager;
        $this->facetConfig = $facetCfg;
        $config = $this->serviceLocator->get(\VuFind\Config\PluginManager::class);
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
        $params = $event->getParam('params');
        $sourcePriority = $this->determineRecordPriority($params);

        $idList = [];
        // Find out the best records and list their IDs:
        /**
         * Result
         *
         * @var \VuFindSearch\Backend\Solr\Response\Json\RecordCollection
         */
        $result = $event->getTarget();
        foreach ($result->getRecords() as $record) {
            $fields = $record->getRawData();

            if (!isset($fields['merged_boolean'])) {
                continue;
            }
            $localIds = $fields['local_ids_str_mv'];
            $dedupId = $localIds[0];
            $priority = 99999;
            $undefPriority = 99999;
            // Find the document that matches the source priority best:
            $dedupData = [];
            foreach ($localIds as $localId) {
                $localPriority = null;
                list($source) = explode('.', $localId, 2);
                if (isset($sourcePriority[$source])) {
                    $localPriority = $sourcePriority[$source];
                } else {
                    $localPriority = ++$undefPriority;
                }
                if (isset($localPriority) && $localPriority < $priority) {
                    $dedupId = $localId;
                    $priority = $localPriority;
                }
                $dedupData[$source] = [
                    'id' => $localId,
                    'priority' => $localPriority ?? 99999
                ];
            }
            $fields['dedup_id'] = $dedupId;
            $idList[] = $dedupId;

            // Sort dedupData by priority:
            uasort(
                $dedupData,
                function ($a, $b) {
                    return $a['priority'] - $b['priority'];
                }
            );
            $fields['dedup_data'] = $dedupData;
            $record->setRawData($fields);
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
        return $this->determinePriorityFromLibraryCards() ?:
            $this->determinePriorityFromFilters($params) ?:
            $this->determineDefaultPriority();
    }

    /**
     * Determine priority from user's library cards
     *
     * @return array
     */
    public function determinePriorityFromLibraryCards()
    {
        $user = $this->authManager->isLoggedIn();
        if (!$user || !$user->libraryCardsEnabled()) {
            return [];
        }
        $myLibs = [];
        foreach ($user->getLibraryCards() as $libCard) {
            $ids = explode('.', $libCard['cat_username'] ?? '', 2);
            if (count($ids) == 2) {
                $myLibs[] = $ids[0];
            }
        }
        return array_flip(array_unique($myLibs));
    }

    /**
     * Determine priority from filters
     *
     * @param ParamBag $params parameters
     *
     * @return array preferred institutions from user library cards
     */
    public function determinePriorityFromFilters($params)
    {
        $config = $this->serviceLocator->get(\VuFind\Config\PluginManager::class);
        $facetConfig = $config->get($this->facetConfig);
        if (!isset($facetConfig->InstitutionsMappings)) {
            return [];
        }
        $institutionMappings = array_flip(
            $facetConfig->InstitutionsMappings->toArray()
        );
        $result = [];
        foreach ($params->get('fq') as $fq) {
            if (preg_match(self::OR_FACETS_REGEX, $fq, $matches)) {
                $field = $matches[2];
                if ($field != $this->institutionField) {
                    continue;
                }
                $filters = explode('OR', $matches[3]);
                foreach ($filters as $filter) {
                    if (preg_match(self::FILTER_REGEX, $filter, $matches)) {
                        $value = $matches[2];
                        $prefix = $institutionMappings[$value];
                        if ($prefix) {
                            $result[] = $prefix;
                        }
                    }
                }
            } elseif (preg_match(self::FILTER_REGEX, $fq, $matches)) {
                $field = $matches[1];
                if ($field != $this->institutionField) {
                    continue;
                }
                $value = $matches[2];
                $prefix = $institutionMappings[$value];
                if ($prefix) {
                    $result[] = $prefix;
                }
            }
        }
        $result = array_flip($result);
        return $result;
    }

    /**
     * Determine default priority from configuration
     *
     * @return array
     */
    public function determineDefaultPriority()
    {
        $config = $this->serviceLocator->get(\VuFind\Config\PluginManager::class);
        $searchConfig = $config->get($this->searchConfig);
        return !empty($searchConfig->Records->sources)
            ? array_flip(explode(',', $searchConfig->Records->sources))
            : [];
    }
}
