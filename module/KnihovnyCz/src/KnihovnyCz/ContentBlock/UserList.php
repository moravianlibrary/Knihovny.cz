<?php

/**
 * Class UserList
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2019.
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
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\ContentBlock;

use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Select;

/**
 * Class UserList
 *
 * @category VuFind
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserList extends AbstractDbAwaredRecordIds
{
    /**
     * User list id
     *
     * @var string
     */
    protected string $listId;

    /**
     * Table for main list
     *
     * @var string
     */
    protected string $listTableName = \VuFind\Db\Table\UserList::class;

    /**
     * Table for list items
     *
     * @var string
     */
    protected string $itemsTableName = \VuFind\Db\Table\UserResource::class;

    /**
     * Modify select for getting list items
     *
     * @param Select $select SQL select object
     *
     * @return void
     */
    protected function setSelect(Select $select): void
    {
        $select->where->equalTo('list_id', $this->listId);
        $select->columns(['id']);
        $select->join(
            'resource',
            'resource.id = user_resource.resource_id',
            ['record_id']
        );
    }

    /**
     * Takes and returns record ids from result set
     *
     * @param ResultSetInterface $items List items
     *
     * @return array
     */
    protected function getIds(ResultSetInterface $items): array
    {
        return array_column($items->toArray(), 'record_id');
    }

    /**
     * Get slug identifier to search for
     *
     * @return string
     */
    protected function getSlug(): string
    {
        return ($this->getList()) ? $this->getList()->getSlug() : '';
    }

    /**
     * Store the configuration of the content block.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        $parsedSettings = explode(':', $settings);
        $this->listId = $parsedSettings[0];
        $this->limit = (int)($parsedSettings[1] ?? 5);
        $this->listParams = [ 'id' => $this->listId, 'public' => 1];
    }
}
