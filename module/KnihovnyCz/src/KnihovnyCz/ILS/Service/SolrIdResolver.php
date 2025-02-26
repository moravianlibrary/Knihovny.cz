<?php

declare(strict_types=1);

namespace KnihovnyCz\ILS\Service;

use VuFindSearch\Command\SearchCommand;
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
                $queryFieldPrefix = $record['base'] ?? $config['solrQueryFieldPrefix'] ?? '';
                $identifier = !empty($queryFieldPrefix)
                    ? $queryFieldPrefix . '.' . $identifier : $identifier;
                $idsToResolve[] = $identifier;
            }
        }
        if (empty($idsToResolve)) {
            return $recordsToResolve;
        }
        $resolved = $this->convertToIdUsingSolr($idsToResolve, $config);
        $results = [];
        $source = $config['source'] ?? null;
        foreach ($recordsToResolve as $record) {
            $itemId = $record[$itemIdentifier] ?? null;
            // Set dummy record id with source when missing
            $record['id'] = $resolved[$itemId] ?? $record['id']
                ?? (isset($source) ? $source . '.' : null);
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
    public function convertToIdUsingSolr(array $ids, array $config): array
    {
        $results = [];
        if (empty($ids)) {
            return $results;
        }
        $queryField = $config['solrQueryField'] ?? $this->defaultSolrQueryField;
        $fq = [ 'merged_child_boolean:true' ];
        if (isset($config['sourceFilter'])) {
            $fq[] = 'id:' . $config['sourceFilter'] . '.*';
        }
        $params = new \KnihovnyCz\Search\ParamBag(
            [
            'fq' => $fq,
            'fl' => "id,$queryField",
            ]
        );
        $params->setApplyChildFilter(false);
        $fullQuery = new \VuFindSearch\Query\QueryGroup('OR');
        $idMappings = [];
        $separateIdParts = $config['separateIdParts'] ?? true;
        foreach ($ids as $idForQuery) {
            $id = $idForQuery;
            if ($separateIdParts) {
                $idsParts = explode('.', $idForQuery);
                if (count($idsParts) === 2) {
                    $id = $idsParts[1];
                }
            }
            $idMappings[$idForQuery] = $id;
            $query = new \VuFindSearch\Query\Query($queryField . ':' . $idForQuery);
            $fullQuery->addQuery($query);
        }
        $command = new SearchCommand('Solr', $fullQuery, 0, count($ids), $params);
        $searchResults = $this->searchService->invoke($command)->getResult();
        foreach ($searchResults->getRecords() as $record) {
            $fields = $record->getRawData();
            $fieldVals = $fields[$queryField] ?? [];
            if (is_scalar($fieldVals)) {
                $fieldVals = [ $fieldVals ];
            }
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
