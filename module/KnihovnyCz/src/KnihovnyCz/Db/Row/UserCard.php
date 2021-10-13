<?php

/**
 * Class UserCard
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
 * @package  KnihovnyCz\Db\Row
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Db\Row;

use VuFind\Db\Row\UserCard as Base;

/**
 * Class UserCard
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Row
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 *
 * @property int     $id
 * @property int     $user_id
 * @property string  $card_name
 * @property string  $cat_username
 * @property ?string $cat_password
 * @property ?string $cat_pass_enc
 * @property string  $home_library
 * @property string  $created
 * @property string  $saved
 * @property ?string $eppn
 * @property ?string $major
 */
class UserCard extends Base
{
    /**
     * Get EduPersonPrincipalName scope
     *
     * @return string|null
     */
    public function getEppnDomain(): ?string
    {
        $array = explode('@', $this->eppn ?? '');
        return end($array) ?? null;
    }
}
