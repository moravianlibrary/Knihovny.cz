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

use KnihovnyCz\Db\Row\User as UserRow;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Update;

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
     * Retrieve a user object from the database based on eduPersonUniqueId.
     *
     * @param string $eduPersonUniqueId eduPersonUniqueId
     *
     * @return UserRow
     */
    public function getByEduPersonUniqueId($eduPersonUniqueId)
    {
        $callback = function ($select) use ($eduPersonUniqueId) {
            $select->join(
                ['uc' => 'user_card'],
                'user.id = uc.user_id',
                []
            );
            $select->where->equalTo('uc.edu_person_unique_id', $eduPersonUniqueId);
        };
        $row = $this->select($callback)->current();
        return empty($row) ? null : $row;
    }

    /**
     * Update the select statement to find records to delete.
     *
     * @param Select $select    Select clause
     * @param string $dateLimit Date threshold of an "expired" record in format
     * 'Y-m-d H:i:s'.
     *
     * @return void
     */
    protected function expirationCallback($select, $dateLimit)
    {
        $select->where->lessThan('last_login', $dateLimit);
    }

    /**
     * This method basically replaces all occurrences of $from->id (UserRow id)
     * in tables comments, user_resource, user_list, user_card and search with
     * $into->id in user_id column.
     *
     * @param UserRow $from from
     * @param UserRow $into into
     *
     * @throws \Exception
     *
     * @return void
     */
    public function merge(UserRow $from, UserRow $into)
    {
        // do it in transaction
        $this->getDbConnection()->beginTransaction();
        $institutions = [];
        /**
         * Merge source library card
         *
         * @var \KnihovnyCz\Db\Row\UserCard $fromCard
         */
        foreach ($from->getLibraryCards() as $fromCard) {
            $prefix = explode('.', $fromCard->cat_username)[0];
            $institutions[$prefix] = $fromCard;
        }
        /**
         * Merge target library card
         *
         * @var \KnihovnyCz\Db\Row\UserCard $intoCard
         */
        foreach ($into->getLibraryCards() as $intoCard) {
            $prefix = explode('.', $intoCard->cat_username)[0];
            if (isset($institutions[$prefix])) {
                $fromCard = $institutions[$prefix];
                $srcEpui = $fromCard->edu_person_unique_id;
                $targetEpui = $intoCard->edu_person_unique_id;
                if ($srcEpui == $targetEpui) {
                    $fromCard->delete();
                } else {
                    $this->getDbConnection()->rollback();
                    throw new \VuFind\Exception\LibraryCard(
                        'Could not connect '
                        . 'users with different library cards from the '
                        . 'same institution'
                    );
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
            $update->set(
                [
                'user_id' => $into->id
                ]
            );
            $update->where(
                [
                'user_id' => $from->id
                ]
            );
            $this->sql->prepareStatementForSqlObject($update)->execute();
        }

        // Perform User deletion
        $from->delete();
        $this->getDbConnection()->commit();
    }

    /**
     * Returns database connection.
     *
     * @return \Laminas\Db\Adapter\Driver\ConnectionInterface $conn
     */
    protected function getDbConnection()
    {
        return $this->getAdapter()->driver->getConnection();
    }
}
