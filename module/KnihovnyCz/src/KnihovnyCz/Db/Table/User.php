<?php

/**
 * Class User
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
 * @package  KnihovnyCz\Db\Table
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Db\Table;

use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Update;
use KnihovnyCz\Db\Row\User as UserRow;

/**
 * Class User
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Josef Moravec <moravec@mzk.cz>
 * @author   Jiří Kozlovský <mail@jkozlovsky.cz>
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class User extends \VuFind\Db\Table\User
{
    use \VuFind\Db\Table\ExpirationTrait;

    /**
     * Retrieve a user object from the database based on eduPersonUniqueId
     * or create new one.
     *
     * @param string $eduPersonUniqueId eduPersonUniqueId
     *
     * @return UserRow
     */
    public function getByEduPersonUniqueId($eduPersonUniqueId)
    {
        $callback = function ($select) use ($eduPersonUniqueId) {
            $select->join(
                ['uc' => 'user_card'], 'user.id = uc.user_id',
                []
            );
            $select->where->equalTo('uc.edu_person_unique_id', $eduPersonUniqueId);
        };
        $row = $this->select($callback)->current();
        if (empty($row)) {
            $row = $this->createRow();
            $row->created = date('Y-m-d H:i:s');
            $row->username = $eduPersonUniqueId;
            // Failing to initialize this here can cause Laminas\Db errors in
            // the VuFind\Auth\Shibboleth and VuFind\Auth\ILS integration tests.
            $row->user_provided_email = 0;
        }
        return $row;
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
        $where = $select->where->lessThan('last_login', $expireDate);
        if (null !== $idFrom) {
            $where->and->greaterThanOrEqualTo('id', $idFrom);
        }
        if (null !== $idTo) {
            $where->and->lessThanOrEqualTo('id', $idTo);
        }
    }

    /**
     * This method basically replaces all occurrences of $from->id (UserRow id) in tables
     * comments, user_resource, user_list & search with $into->id in user_id column.
     *
     * @param UserRow $from
     * @param UserRow $into
     * @throws AuthException
     * @return void
     */
    public function merge(UserRow $from, UserRow $into)
    {
        // TODO: do it in transaction
        $institutions = [];
        foreach ($from->getLibraryCards() as $fromCard) {
            $prefix = explode('.', $fromCard->cat_username)[0];
            $institutions[$prefix] = $fromCard;
        }
        foreach ($into->getLibraryCards() as $intoCard) {
            $prefix = explode('.', $intoCard->cat_username)[0];
            if (isset($institutions[$prefix])) {
                $fromCard = $institutions[$prefix];
                if ($fromCard->edu_person_unique_id
                    == $intoCard->edu_person_unique_id) {
                    $fromCard->remove();
                } else {
                    throw new \Exception('Could not connect users');
                }
            }
        }

        /**
         * Table names which contain user_id as a relation to user.id foreign key
         */
        $tables = [
            "user_card",
            "comments",
            "user_resource",
            "user_list",
            "search"
        ];

        foreach ($tables as $table) {
            $update = new Update($table);
            $update->set([
                'user_id' => $into->id
            ]);
            $update->where([
                'user_id' => $from->id
            ]);
            $statement = $this->sql->prepareStatementForSqlObject($update);
            $result = $statement->execute();
        }

        // Perform User deletion
        $from->delete();
    }

}
