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

class User extends \VuFind\Db\Table\User
{
    /**
     * Get a query representing expired user accounts (this can be passed
     * to select() or delete() for further processing).
     *
     * @param int $daysOld Days from last_login
     *
     * @return callable
     */
    public function getExpiredQuery($daysOld = 730)
    {
        // Determine the expiration date:
        $expireDate = date('Y-m-d', strtotime(sprintf('-%d days', (int)$daysOld)));
        return function ($select) use ($expireDate) {
            $select->where->lessThan('last_login', $expireDate);
        };
    }
}
