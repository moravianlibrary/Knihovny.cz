<?php

/**
 * Class Inspiration
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
 * Class Inspiration
 *
 * @category VuFind
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Inspiration extends AbstractDbAwaredRecordIds
{
    /**
     * Widget key
     *
     * @var string
     */
    protected string $key;

    /**
     * Table for main list
     *
     * @var string
     */
    protected string $listTableName = \KnihovnyCz\Db\Table\Widget::class;

    /**
     * Table for list items
     *
     * @var string
     */
    protected string $itemsTableName = \KnihovnyCz\Db\Table\WidgetContent::class;

    /**
     * Modify select for getting list items
     *
     * @param Select $select
     *
     * @return void
     */
    protected function setSelect(Select $select): void
    {
        $select->where(['widget_id' => $this->listRow->id]);
    }

    /**
     * Takes and returns record ids from result set
     *
     * @param ResultSetInterface $items
     *
     * @return array
     */
    protected function getIds(ResultSetInterface $items): array
    {
        return array_column($items->toArray(), 'value');
    }

    /**
     * Get slug identifier to search for
     *
     * @return string
     */
    protected function getSlug(): string
    {
        return ($this->getList()) ? $this->getList()['name'] : '';
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
        $this->key = $parsedSettings[0];
        $this->limit = (int)($parsedSettings[1] ?? 5);
        $this->listParams = [ 'name' => $this->key ];
    }
}
