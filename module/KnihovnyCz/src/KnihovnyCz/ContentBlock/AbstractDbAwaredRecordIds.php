<?php
declare(strict_types=1);

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

namespace KnihovnyCz\ContentBlock;

use Laminas\Db\Sql\Predicate\Expression;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager as TableManager;
use VuFind\Record\Loader as RecordLoader;

/**
 * Class AbstractDbAwaredRecordIds
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
abstract class AbstractDbAwaredRecordIds
    implements \VuFind\ContentBlock\ContentBlockInterface
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
     * Table manager
     *
     * @var TableManager
     */
    protected TableManager $tableManager;

    /**
     * Record loader
     *
     * @var RecordLoader
     */
    protected RecordLoader $recordLoader;

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
     * Limit
     *
     * @var int
     */
    protected int $limit = 5;

    /**
     * Constructor
     *
     * @param TableManager $tables Table manager
     * @param RecordLoader $loader Record loader
     */
    public function __construct(TableManager $tables, RecordLoader $loader)
    {
        $this->tableManager = $tables;
        $this->recordLoader = $loader;
    }

    /**
     * Load records in batch
     *
     * @param array $ids recod identifiers
     *
     * @return array
     * @throws \Exception
     */
    protected function loadRecords(array $ids): array
    {
        $ids = array_map(function ($id) {
            return $this->searchClassId . '|' . $id;
        }, $ids);
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
    public function getItems()
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
    protected abstract function getSlug(): string;

    /**
     * Return context variables used for rendering the block's template.
     *
     * @return array
     */
    public function getContext()
    {
        return [
            'searchClassId' => $this->searchClassId,
            'facetField' => $this->facetField,
            'searchType' => $this->searchType,
            'list' => $this->getList(),
            'slug' => $this->getSlug(),
            'items' => $this->getItems(),
        ];
    }
}
