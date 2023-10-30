<?php

namespace KnihovnyCz\Search\Solr;

use Laminas\EventManager\EventInterface;

/**
 * Solr deduplication listener for views.
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class OneChildDocDeduplicationListener extends ChildDocDeduplicationListener
{
    /**
     * Maximal number of child documents to fetch
     *
     * @var int
     */
    protected const MAX_CHILD_DOCUMENTS = 1;

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
            $parentRawData = $record->getRawData();
            $childRawData = $parentRawData['childs']['docs'][0];
            $childRawData['parent_data'] = $parentRawData;
            $childRawData['local_ids_str_mv'] = $this
                ->getLocalRecordIds($parentRawData);
            $recordFactory = $this->serviceLocator
                ->get(\VuFind\RecordDriver\PluginManager::class);
            $newRecord = $recordFactory->getSolrRecord($childRawData);
            $newRecord->setHighlightDetails($record->getHighlightDetails());
            $newRecord->setSourceIdentifiers($record->getSourceIdentifier());
            $result->replace($record, $newRecord);
        }
    }

    /**
     * Return new list of fields to fetch from Solr for child record
     *
     * @param string $parentFieldList field list used for fetching parent records
     *
     * @return string|null
     */
    protected function getChildListOfFields($parentFieldList)
    {
        return $parentFieldList;
    }
}
