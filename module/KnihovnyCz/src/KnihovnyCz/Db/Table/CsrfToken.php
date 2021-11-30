<?php

/**
 * Class CsrfToken
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
namespace KnihovnyCz\Db\Table;

use KnihovnyCz\Db\Row\User as UserRow;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\Gateway;
use VuFind\Db\Table\PluginManager;

/**
 * Class CsrfToken
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class CsrfToken extends Gateway
{
    use \VuFind\Db\Table\ExpirationTrait;

    /**
     * Constructor
     *
     * @param Adapter       $adapter Database adapter
     * @param PluginManager $tm      Table manager
     * @param array         $cfg     Laminas configuration
     * @param RowGateway    $rowObj  Row prototype object (null for default)
     * @param string        $table   Name of database table to interface with
     */
    public function __construct(Adapter $adapter, PluginManager $tm, $cfg,
        RowGateway $rowObj = null, $table = 'csrf_token'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Retrieve a user object from the database based on eduPersonUniqueId
     * or create new one.
     *
     * @param string $sid   Session ID of current user.
     * @param string $token Token value
     *
     * @return UserRow
     */
    public function findBySessionAndHash(string $sid, string $token)
    {
        return $this->select(
            [
            'session_id' => $sid,
            'token'    => $token,
            ]
        )->current();
    }

    /**
     * Update the select statement to find records to delete.
     *
     * @param Select $select  Select clause
     * @param int    $daysOld Age in days of an "expired" record.
     * @param int    $idFrom  Lowest id of rows to delete.
     * @param int    $idTo    Highest id of rows to delete.
     *
     * @return void
     */
    protected function expirationCallback($select, $daysOld, $idFrom = null,
        $idTo = null
    ) {
        $timestamp = strtotime(sprintf('-%d days', (int)$daysOld));
        if ($timestamp === false) {
            throw new \Exception('Could not parse timestamp');
        }
        $expireDate = date('Y-m-d', $timestamp);
        $where = $select->where->lessThan('created', $expireDate);
        if (null !== $idFrom) {
            $where->and->greaterThanOrEqualTo('id', $idFrom);
        }
        if (null !== $idTo) {
            $where->and->lessThanOrEqualTo('id', $idTo);
        }
    }
}
