<?php

/**
 * Class UserSettings
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
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Db\Table;

use Laminas\Db\Adapter\Adapter;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\Gateway;
use VuFind\Db\Table\PluginManager;

/**
 * Class UserSettings
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserSettings extends Gateway
{
    /**
     * Constructor
     *
     * @param Adapter       $adapter Database adapter
     * @param PluginManager $tm      Table manager
     * @param array         $cfg     Laminas configuration
     * @param RowGateway    $rowObj  Row prototype object (null for default)
     * @param string        $table   Name of database table to interface with
     */
    public function __construct(
        Adapter $adapter,
        PluginManager $tm,
        $cfg,
        RowGateway $rowObj = null,
        $table = 'user_settings'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Retrieve a user setting object from the database based on user id.
     *
     * @param int $userId user id
     *
     * @return UserSettings
     */
    public function getOrCreateByUserId($userId)
    {
        $callback = function ($select) use ($userId) {
            $select->where->equalTo('user_id', $userId);
        };
        $row = $this->select($callback)->current();
        if ($row == null) {
            $row = $this->createRow();
            $row->user_id = $userId;
            $row->saved_institutions = '';
            $row->save();
        }
        return $row;
    }
}
