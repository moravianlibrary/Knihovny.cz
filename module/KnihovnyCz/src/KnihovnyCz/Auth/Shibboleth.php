<?php
/**
 * Shibboleth authentication module.
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

use \VuFind\Auth\Shibboleth as Base;
use \VuFind\Auth\Shibboleth\ConfigurationLoaderInterface;
/**
 * Shibboleth authentication module.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link https://vufind.org Main Page
 */
class Shibboleth extends Base
{

    /**
     * Constructor
     *
     * @param \Laminas\Session\ManagerInterface    $sessionManager      Session
     * manager
     * @param ConfigurationLoaderInterface         $configurationLoader Configuration
     * loader
     * @param \Laminas\Http\PhpEnvironment\Request $request             Http
     * request object
     */
    public function __construct(\Laminas\Session\ManagerInterface $sessionManager,
        ConfigurationLoaderInterface $configurationLoader,
        \Laminas\Http\PhpEnvironment\Request $request
    ) {
        parent::__construct($sessionManager, $configurationLoader, $request);
        $this->attribsToCheck[] = 'edu_person_unique_id';
    }

    /**
     * Attempt to authenticate the current user.  Throws exception if login fails.
     *
     * @param \Laminas\Http\PhpEnvironment\Request $request Request object containing
     * account credentials.
     *
     * @throws AuthException
     * @return \VuFind\Db\Row\User Object representing logged-in user.
     */
    public function authenticate($request)
    {
        // validate config before authentication
        $this->validateConfig();
        // Check if username is set.
        $entityId = $this->getCurrentEntityId($request);
        $shib = $this->getConfigurationLoader()->getConfiguration($entityId);
        $eduPersonUniqueId = $this->getAttribute($request, $shib['edu_person_unique_id']);
        if (empty($eduPersonUniqueId)) {
            $details = ($this->useHeaders) ? $request->getHeaders()->toArray()
                : $request->getServer()->toArray();
            $this->debug(
                "No eduPersonUniqueId attribute "
                . "({$shib['edu_person_unique_id']}) "
                . "present in request: " . print_r($details, true)
            );
            throw new AuthException('authentication_error_admin');
        }

        // Check if required attributes match up:
        foreach ($this->getRequiredAttributes($shib) as $key => $value) {
            if (!preg_match("/$value/", $this->getAttribute($request, $key))) {
                $details = ($this->useHeaders) ? $request->getHeaders()->toArray()
                    : $request->getServer()->toArray();
                $this->debug(
                    "Attribute '$key' does not match required value '$value' in"
                    . ' request: ' . print_r($details, true)
                );
                throw new AuthException('authentication_error_denied');
            }
        }
        $user = $this->getUserTable()->getByEduPersonUniqueId($eduPersonUniqueId);
        if ($user->username == null) {
            $username = $this->getAttribute($request, $shib['username']);
            $user->username = $username ?? $eduPersonUniqueId;
        }

        // Variable to hold catalog password (handled separately from other
        // attributes since we need to use saveCredentials method to store it):
        $catPassword = null;

        // Has the user configured attributes to use for populating the user table?
        foreach ($this->attribsToCheck as $attribute) {
            if (isset($shib[$attribute])) {
                $value = $this->getAttribute($request, $shib[$attribute]);
                if ($attribute == 'email') {
                    $user->updateEmail($value);
                } elseif ($attribute == 'cat_username' && isset($shib['prefix'])
                    && !empty($value)
                ) {
                    $user->cat_username = $shib['prefix'] . '.' . $value;
                } elseif ($attribute == 'cat_password') {
                    $catPassword = $value;
                } else {
                    $user->$attribute = $value ?? '';
                }
            }
        }

        // Save credentials if applicable. Note that we want to allow empty
        // passwords (see https://github.com/vufind-org/vufind/pull/532), but
        // we also want to be careful not to replace a non-blank password with a
        // blank one in case the auth mechanism fails to provide a password on
        // an occasion after the user has manually stored one. (For discussion,
        // see https://github.com/vufind-org/vufind/pull/612). Note that in the
        // (unlikely) scenario that a password can actually change from non-blank
        // to blank, additional work may need to be done here.
        if (!empty($user->cat_username)) {
            $user->saveCredentials(
                $user->cat_username,
                empty($catPassword) ? $user->getCatPassword() : $catPassword
            );
        }

        $this->storeShibbolethSession($request);

        // Save and return the user object:
        $user->save();
        return $user;
    }

    /**
     * Connect user authenticated by Shibboleth to library card.
     *
     * @param \Laminas\Http\PhpEnvironment\Request $request        Request object
     * containing account credentials.
     * @param \VuFind\Db\Row\User                  $connectingUser Connect newly
     * created library card to this user.
     *
     * @return void
     */
    public function connectLibraryCard($request, $connectingUser)
    {
        $entityId = $this->getCurrentEntityId($request);
        $shib = $this->getConfigurationLoader()->getConfiguration($entityId);
        $username = $this->getAttribute($request, $shib['cat_username']);
        if (!$username) {
            throw new \VuFind\Exception\LibraryCard('Missing username');
        }
        $prefix = $shib['prefix'] ?? '';
        if (!empty($prefix)) {
            $username = $shib['prefix'] . '.' . $username;
        }
        $password = $shib['cat_password'] ?? null;
        $cardId = $connectingUser->saveLibraryCard(
            null, $prefix,
            $username, $password
        );
        if (isset($shib['edu_person_unique_id'])) {
            $eduPersonUniqueId = $this->getAttribute($request,
                $shib['edu_person_unique_id']);
            $card = $connectingUser->getLibraryCard($cardId);
            $card->edu_person_unique_id = $eduPersonUniqueId;
            $card->save();
        }
    }

}