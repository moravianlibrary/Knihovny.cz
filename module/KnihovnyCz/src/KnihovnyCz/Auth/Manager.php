<?php
/**
 * Wrapper class for handling logged-in user in session.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2014.
 * Copyright (C) The National Library of Finland 2016.
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
 * @package  Authentication
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link https://vufind.org Main Page
 */
namespace KnihovnyCz\Auth;

use \VuFind\Auth\Manager as Base;
/**
 * Wrapper class for handling logged-in user in session.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link https://vufind.org Main Page
 */
class Manager extends Base
{

    public function isLoggedIn()
    {
        // modification for GDPR - do not store last name, first name and email
        // in database
        $user = parent::isLoggedIn();
        if ($user && isset($this->session->userInfo)) {
            $userInfo = $this->session->userInfo;
            $user->lastname = $userInfo['lastname'];
            $user->firstname = $userInfo['firstname'];
            $user->email = $userInfo['email'];
        }
        return $user;
    }

}