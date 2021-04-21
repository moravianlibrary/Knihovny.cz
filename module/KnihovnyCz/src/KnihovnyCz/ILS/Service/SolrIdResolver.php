<?php
declare(strict_types=1);

/**
 * Class SolrIdResolver
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @author   Vaclav Rosecky <xrosecky@gmail.com>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\ILS\Service;

use VuFindSearch\Service as SearchService;

/**
 * Class SolrIdResolver
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @author   Vaclav Rosecky <xrosecky@gmail.com>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SolrIdResolver
{
    /**
     * Solr field to search in
     *
     * @var string
     */
    protected string $defaultSolrQueryField = 'availability_id_str_mv';

    /**
     * Item identifier field in item data array
     *
     * @var string
     */
    protected string $defaultItemIdentifier = 'adm_id';

    /**
     * Search service (used for lookups by item identifiers)
     *
     * @var SearchService
     */
    protected SearchService $searchService;

    /**
     * SolrIdResolver constructor.
     *
     * @param SearchService $searchService VuFind search service
     */
    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Resolve id in solr using item identifiers
     *
     * @param array $recordsToResolve Records from ILS driver
     * @param array $config           Resolving configuration
     *
     * @return array
     */
    public function resolveIds(array $recordsToResolve, array $config): array
    {
        $itemIdentifier = $config['itemIdentifier'] ?? $this->defaultItemIdentifier;
        $idsToResolve = [];
        foreach ($recordsToResolve as $record) {
            $identifier = $record[$itemIdentifier] ?? null;
            if (!empty($identifier)) {
                $idsToResolve[] = $identifier;
            }
        }
        $resolved = $this->convertToIdUsingSolr($idsToResolve, $config);
        $results = [];
        foreach ($recordsToResolve as $record) {
            $itemId = $record[$itemIdentifier] ?? null;
            $record['id'] = $resolved[$itemId] ?? $record['id'];
            $results[] = $record;
        }
        return $results;
    }

    /**
     * Do the actual id resolving
     *
     * @param array $ids    Item identifiers
     * @param array $config Resolving configuration
     *
     * @return array
     */
    protected function convertToIdUsingSolr(array $ids, array $config): array
    {
        $results = [];
        if (empty($ids)) {
            return $results;
        }
        $queryField = $config['solrQueryField'] ?? $this->defaultSolrQueryField;
        $queryFieldPrefix = $config['solrQueryFieldPrefix'] ?? '';
        $params = new \VuFindSearch\ParamBag(
            [
            'fq' => ['merged_child_boolean:true'], 'fl' => "id,$queryField",
            ]
        );
        $fullQuery = new \VuFindSearch\Query\QueryGroup('OR');
        $idMappings = [];
        foreach ($ids as $id) {
            $idForQuery = $queryFieldPrefix . '.' . $id;
            $idMappings[$idForQuery] = $id;
            $query = new \VuFindSearch\Query\Query($queryField . ':' . $idForQuery);
            $fullQuery->addQuery($query);
        }
        $searchResults = $this->searchService->search(
            'Solr', $fullQuery, 0, sizeof($ids), $params
        );
        foreach ($searchResults->getRecords() as $record) {
            $fields = $record->getRawData();
            $fieldVals = $fields[$queryField] ?? [];
            foreach ($fieldVals as $value) {
                $originalId = $idMappings[$value] ?? '';
                if (!empty($originalId)) {
                    $results[$originalId] = $record->getUniqueID();
                }
            }
        }
        return $results;
    }
}
