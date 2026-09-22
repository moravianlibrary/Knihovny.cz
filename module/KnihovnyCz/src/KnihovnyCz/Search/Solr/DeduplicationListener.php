<?php

declare(strict_types=1);

namespace KnihovnyCz\Search\Solr;

use KnihovnyCz\Service\PreferredInstitutionsService;
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
    public const UNDEF_PRIORITY = 99999;

    public const MIN_PRIORITY = 999999;

    /**
     * Institution mappings service
     *
     * @var PreferredInstitutionsService
     */
    protected PreferredInstitutionsService $preferredInstitutionsService;

    /**
     * Auth Manager
     *
     * @var \VuFind\Auth\Manager
     */
    protected \VuFind\Auth\Manager $authManager;

    /**
     * Solr institution field
     *
     * @var string
     */
    protected string $institutionField = 'region_institution_facet_mv';

    /**
     * Constructor.
     *
     * @param Backend            $backend        Search backend
     * @param ContainerInterface $serviceLocator Service locator
     * @param string             $searchCfg      Search config file id
     * @param string             $facetCfg       Facet config file id
     * @param string             $dataSourceCfg  Data source file id
     * @param bool               $enabled        Whether deduplication is enabled
     */
    public function __construct(
        Backend $backend,
        ContainerInterface $serviceLocator,
        string $searchCfg,
        private string $facetCfg,
        string $dataSourceCfg = 'datasources',
        bool $enabled = true
    ) {
        parent::__construct(
            $backend,
            $serviceLocator,
            $searchCfg,
            $dataSourceCfg,
            $enabled
        );
        $this->preferredInstitutionsService = $this->serviceLocator->
            get(\KnihovnyCz\Service\PreferredInstitutionsService::class);
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
                [$source] = explode('.', $localId, 2);
                $localPriority = $order = $sourcePriority[$source] ?? null;
                if ($localPriority === null) {
                    $localPriority = ++$undefPriority;
                    $order = PHP_INT_MAX;
                }
                $dedupData[$source] = [
                    'id' => $localId,
                    'priority' => $localPriority,
                    'order' => $order,
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
            $foundLocalRecord->setSourceIdentifiers($record->getSourceIdentifier());
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
        $sources = $this->preferredInstitutionsService->getSourcesFromParametersAndUser($params);
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
        $localRecordData = parent::appendDedupRecordFields(
            $localRecordData,
            $dedupRecordData,
            $recordSources,
            $sourcePriority
        );
        $localRecordData['parent_data'] = $dedupRecordData;
        return $localRecordData;
    }
}
