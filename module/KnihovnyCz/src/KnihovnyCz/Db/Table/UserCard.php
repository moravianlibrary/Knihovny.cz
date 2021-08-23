<?php

/**
 * Class UserCard
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
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Db\Table;

use Laminas\Db\Sql\Select;

/**
 * Class User
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserCard extends \VuFind\Db\Table\UserCard
{

    /**
     * Retrieve a user card object from the database based on eduPersonUniqueId
     * or create new one.
     *
     * @param string $id ID.
     * @param boolean $create create new user card
     *
     * @return UserRow
     */
    public function getByEduPersonUniqueId($eduPersonUniqueId)
    {
        $callback = function ($select) use ($eduPersonUniqueId) {
            $select->where->equalTo('edu_person_unique_id', $eduPersonUniqueId);
        };
        $row = $this->select($callback)->current();
        return $row;
    }

}
