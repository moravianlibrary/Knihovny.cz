<?php

/**
 * Class UserList
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\Db\Table
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Db\Table;

use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Predicate\Like as LikePredicate;
use Laminas\Db\Sql\Select;

/**
 * Class UserList
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Db\Table
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserList extends \VuFind\Db\Table\UserList
{
    /**
     * Get public lists usable as inspiration lists
     *
     * @return ResultSetInterface
     */
    public function getInspirationLists(): ResultSetInterface
    {
        return $this->select(
            function (Select $select) {
                $select->join('user', 'user.id = user_list.user_id', [])
                    ->where(
                        [
                            'user_list.public' => 1,
                            new LikePredicate('user.major', '%widgets%')
                        ]
                    );
            }
        );
    }
}
