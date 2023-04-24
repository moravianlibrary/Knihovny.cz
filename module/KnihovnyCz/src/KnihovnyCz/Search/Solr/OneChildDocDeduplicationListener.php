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
