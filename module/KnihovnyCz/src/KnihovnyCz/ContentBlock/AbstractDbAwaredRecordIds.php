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
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Select;
use Laminas\View\Helper\Url;
use VuFind\ContentBlock\ContentBlockInterface;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager as TableManager;
use VuFind\Record\Loader as RecordLoader;
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
     */
    public function __construct(
        protected readonly TableManager $tableManager,
        protected readonly RecordLoader $recordLoader,
        protected readonly Url $url,
        protected readonly SearchOptionsManager $searchOptions
    ) {
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
        $ids = array_map(
            function ($id) {
                return $this->searchClassId . '|' . $id;
            },
            $ids
        );
        return $this->recordLoader->loadBatch($ids, true);
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
            $select->order(new Expression('RAND()'));
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
        return $this->url->__invoke($searchAction) . "?" . http_build_query(
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
    protected function getListUrl(): string
    {
        return $this->url->__invoke('inspiration-show', ['list' => $this->getSlug()]);
    }
}
