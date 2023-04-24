<?php

/**
 * Class KohaRest
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
 * @package  KnihovnyCz\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\ILS\Driver;

use VuFind\Exception\ILS as ILSException;

/**
 * Class KohaRest
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class KohaRest extends \VuFind\ILS\Driver\KohaRest
{
    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $username The patron username
     * @param string $password The patron password
     *
     * @throws ILSException
     * @return mixed           Associative array of patron info on successful login,
     * null on unsuccessful login.
     */
    public function patronLogin($username, $password)
    {
        $result = $this->makeRequest(['v1', 'patrons', $username]);
        if (200 !== $result['code']) {
            throw new ILSException('Problem with Koha REST API.');
        }

        $data = $result['data'];
        return [
            'id' => $data['patron_id'],
            'firstname' => $data['firstname'],
            'lastname' => $data['surname'],
            'cat_username' => $username,
            'cat_password' => $password,
            'email' => $data['email'],
            'major' => null,
            'college' => $data['category_id'],
            'home_library' => $data['library_id'],
        ];
    }
}
