<?php

/**
 * Class AbstractDbAwaredRecordIds
 *
 * PHP version 8
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\ContentBlock;

use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Select;
use Laminas\View\Helper\Url;
use VuFind\Cache\CacheTrait;
use VuFind\ContentBlock\ContentBlockInterface;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager as TableManager;
use VuFind\Record\Loader as RecordLoader;
use VuFind\RecordDriver\PluginManager as RecordFactory;
use VuFind\Search\Options\PluginManager as SearchOptionsManager;

/**
 * Class AbstractDbAwaredRecordIds
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
abstract class AbstractDbAwaredRecordIds implements ContentBlockInterface
{
    use CacheTrait;

    /**
     * Search class ID to use for retrieving facets.
     *
     * @var string
     */
    protected string $searchClassId = 'Solr';

    /**
     * Facet field
     *
     * @var string
     */
    protected string $facetField = 'inspiration';

    /**
     * Search type
     *
     * @var string
     */
    protected string $searchType = 'AllFields';

    /**
     * List row
     *
     * @var ?RowGateway
     */
    protected ?RowGateway $listRow = null;

    /**
     * List items
     *
     * @var ?array
     */
    protected ?array $items = null;

    /**
     * Params for selecting list
     *
     * @var array
     */
    protected array $listParams;

    /**
     * Table for main list
     *
     * @var string
     */
    protected string $listTableName;

    /**
     * Table for list items
     *
     * @var string
     */
    protected string $itemsTableName;

    /**
     * Limit
     *
     * @var int
     */
    protected int $limit = 5;

    /**
     * Constructor
     *
     * @param TableManager         $tableManager  Table manager
     * @param RecordLoader         $recordLoader  Record loader
     * @param Url                  $url           Url helper
     * @param SearchOptionsManager $searchOptions Search options plugin manager
     * @param RecordFactory        $recordFactory Record driver plugin manager
     */
    public function __construct(
        protected readonly TableManager $tableManager,
        protected readonly RecordLoader $recordLoader,
        protected readonly Url $url,
        protected readonly SearchOptionsManager $searchOptions,
        protected readonly RecordFactory $recordFactory
    ) {
        $this->cacheLifetime = 3600;
    }

    /**
     * Load records in batch
     *
     * @param array $ids record identifiers
     *
     * @return array
     * @throws \Exception
     */
    protected function loadRecords(array $ids): array
    {
        $ids = array_map($this->formatId(...), $ids);
        $records = [];
        $idsToLoad = [];
        foreach ($ids as $id) {
            $record = null;
            $recordData = $this->getCachedData($id);
            if ($recordData !== null) {
                $record = $this->recordFactory->getSolrRecord($recordData);
            }
            if ($record !== null) {
                $records[] = $record;
                continue;
            }
            $idsToLoad[] = $id;
        }
        $recordsFromSolr = $this->recordLoader->loadBatch($idsToLoad, true);
        foreach ($recordsFromSolr as $record) {
            if ($record instanceof \VuFind\RecordDriver\Missing) {
                continue;
            }
            $this->putCachedData($this->formatId($record->getUniqueID()), $record->getRawData());
        }
        $records = array_merge($records, $recordsFromSolr);
        shuffle($records);
        return $records;
    }

    /**
     * Format record identifier
     *
     * @param string $id Record identifier
     *
     * @return string
     */
    protected function formatId(string $id): string
    {
        return $this->searchClassId . '|' . $id;
    }

    /**
     * Get list
     *
     * @return RowGateway|null
     */
    public function getList(): ?RowGateway
    {
        if ($this->listRow === null) {
            $lists = $this->tableManager->get($this->listTableName);
            $this->listRow = $lists->select($this->listParams)->current();
        }
        return $this->listRow;
    }

    /**
     * Get inspiration list items
     *
     * @return array
     */
    public function getItems(): array
    {
        if ($this->items === null) {
            $this->items = [];
            $list = $this->getList();
            if (!$list) {
                return $this->items;
            }
            $itemsTable = $this->tableManager->get($this->itemsTableName);
            $select = $itemsTable->getSql()->select();
            $this->setSelect($select);
            $select->limit($this->limit);
            $items = $itemsTable->selectWith($select);
            $this->items = $this->loadRecords($this->getIds($items));
        }
        return $this->items;
    }

    /**
     * Get slug identifier to search for
     *
     * @return string
     */
    abstract protected function getSlug(): string;

    /**
     * Return context variables used for rendering the block's template.
     *
     * @return array
     */
    public function getContext()
    {
        if ($this->isEmpty()) {
            return [];
        }
        return [
            'list' => $this->getList(),
            'items' => $this->getItems(),
            'searchUrl' => $this->getSearchUrl(),
            'listUrl' => $this->getListUrl(),
        ];
    }

    /**
     * Takes and returns record ids from result set
     *
     * @param ResultSetInterface $items List items
     *
     * @return array
     */
    abstract protected function getIds(ResultSetInterface $items): array;

    /**
     * Modify select for getting list items
     *
     * @param Select $select SQL select object
     *
     * @return void
     */
    abstract protected function setSelect(Select $select): void;

    /**
     * Get search URL
     *
     * @return string
     */
    protected function getSearchUrl(): string
    {
        $options = $this->searchOptions->get($this->searchClassId);
        $searchAction = $options->getSearchAction();
        return $this->url->__invoke($searchAction) . '?' . http_build_query(
            [
                'lookfor' => $this->facetField . ':"' . $this->getSlug() . '"',
                'type' => $this->searchType,
            ]
        );
    }

    /**
     * Get list URL
     *
     * @return string
     */
    public function getListUrl(): string
    {
        return $this->url->__invoke('inspiration-show', ['list' => $this->getSlug()]);
    }

    /**
     * Is list empty?
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->getList() === null;
    }

    /**
     * Method to ensure uniform cache keys for cached VuFind objects.
     *
     * @param string|null $suffix Optional suffix that will get appended to the
     * object class name calling getCacheKey()
     *
     * @return string
     */
    protected function getCacheKey($suffix = null)
    {
        return preg_replace(
            "/([^a-z0-9_\+\-])+/Di",
            '',
            "list$suffix"
        );
    }

    /**
     * Validate slug
     *
     * @param string $slug Slug to validate
     *
     * @return bool
     */
    public function validateSlug(string $slug): bool
    {
        return $slug === $this->getSlug();
    }
}
