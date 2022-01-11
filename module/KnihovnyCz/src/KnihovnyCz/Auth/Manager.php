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
 * @link     https://vufind.org Main Page
 */
namespace KnihovnyCz\Auth;

use VuFind\Auth\Manager as Base;
use VuFind\Db\Row\User;

/**
 * Wrapper class for handling logged-in user in session.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Manager extends Base
{
    /**
     * Checks whether the user is logged in.
     *
     * @return \KnihovnyCz\Db\Row\User|false Object if user is logged in, false
     * otherwise.
     */
    public function isLoggedIn()
    {
        // modification for GDPR - do not store last name, first name and email
        // in database
        /**
         * Logged in user
         *
         * @var \KnihovnyCz\Db\Row\User|false
         */
        $user = parent::isLoggedIn();
        if ($user && isset($this->session->userInfo)) {
            $userInfo = $this->session->userInfo;
            $user->lastname = $userInfo['lastname'];
            $user->firstname = $userInfo['firstname'];
            $user->email = $userInfo['email'];
        }
        return $user;
    }

    /**
     * Does the provided token match the one generated?
     *
     * @param string $value Value to check
     *
     * @return bool
     */
    public function isValidCsrfHash($value)
    {
        return $this->csrf->isValid($value);
    }

    /**
     * Log out the current user.
     *
     * @param string $url       URL to redirect user to after logging out.
     * @param bool   $destroy   Should we destroy the session (true) or just
     * reset it (false); destroy is for log out, reset is for expiration.
     * @param bool   $extLogout Logout also in authentication source
     *
     * @return string     Redirect URL (usually same as $url, but modified in
     * some authentication modules).
     */
    public function logout($url, $destroy = true, $extLogout = true)
    {
        // Perform authentication-specific cleanup and modify redirect URL if
        // necessary.
        if ($extLogout) {
            $url = $this->getAuth()->logout($url);
        }

        // Reset authentication state
        $this->getAuth()->resetState();

        // Clear out the cached user object and session entry.
        $this->currentUser = false;
        unset($this->session->userId);
        unset($this->session->userDetails);
        $this->cookieManager->set('loggedOut', 1);

        // Destroy the session for good measure, if requested.
        if ($destroy) {
            $this->sessionManager->destroy();
        } else {
            // If we don't want to destroy the session, we still need to empty it.
            // There should be a way to do this through Laminas\Session, but there
            // apparently isn't (TODO -- do this better):
            $_SESSION = [];
        }

        return $url;
    }
}
