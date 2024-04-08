<?php

/**
 * Class RecordStatus
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2023.
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
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\Db\Table;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Select;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager;

/**
 * Class RecordStatus
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class RecordStatus extends \VuFind\Db\Table\Gateway
{
    /**
     * Constructor
     *
     * @param Adapter         $adapter Database adapter
     * @param PluginManager   $tm      Table manager
     * @param array           $cfg     Laminas configuration
     * @param RowGateway|null $rowObj  Row prototype object (null for default)
     * @param string          $table   Name of database table to interface with
     */
    public function __construct(
        Adapter $adapter,
        PluginManager $tm,
        array $cfg,
        ?RowGateway $rowObj = null,
        $table = 'record_status'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Find statuses by record ids
     *
     * @param array $recordIds record ids
     *
     * @return ResultSetInterface
     */
    public function getByRecordIds(array $recordIds): ResultSetInterface
    {
        return $this->select(
            function (Select $select) use ($recordIds) {
                $select
                    ->columns(['record_id', 'absent_total',
                        'absent_on_loan', 'present_total', 'present_on_loan'])
                    ->where->in('record_id', $recordIds);
            }
        );
    }
}
