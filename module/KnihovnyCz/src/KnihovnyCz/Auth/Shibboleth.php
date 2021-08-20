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
                } else {
                    $user->$attribute = $value ?? '';
                }
            }
        }

        $this->storeShibbolethSession($request);

        // Save and return the user object:
        $user->save();
        // create library card
        $this->connectLibraryCard($request, $user, true);
        return $user;
    }

    /**
     * Connect user authenticated by Shibboleth to library card.
     *
     * @param \Laminas\Http\PhpEnvironment\Request $request        Request object
     * containing account credentials.
     * @param \VuFind\Db\Row\User                  $connectingUser Connect newly
     * created library card to this user.
     * @param boolean                              $update         Update UserCard
     *
     * @return void
     */
    public function connectLibraryCard($request, $connectingUser)
    {
        $entityId = $this->getCurrentEntityId($request);
        $shib = $this->getConfigurationLoader()->getConfiguration($entityId);
        $eduPersonUniqueId = $this->getAttribute($request, $shib['edu_person_unique_id']);
        $card = $this->getUserCardTable()->getByEduPersonUniqueId($eduPersonUniqueId);
        if ($card->user_id != null && $card->user_id != $connectingUser->id) {
            throw new \VuFind\Exception\LibraryCard(
                'Username is already in use in another library card'
            );
        }
        $username = $this->getAttribute($request, $shib['cat_username']);
        $prefix = $shib['prefix'] ?? '';
        if (!empty($prefix)) {
            $username = $shib['prefix'] . '.' . $username;
        }
        $card->user_id = $connectingUser->id;
        $card->cat_username = $username;
        $card->card_name = $prefix;
        $card->home_library = $prefix;
        $card->eppn = $this->getAttribute($request, $shib['eppn']);
        $card->edu_person_unique_id = $this->getAttribute($request,
            $shib['edu_person_unique_id']);
        $card->save();
    }

    /**
     * Get access to the user table.
     *
     * @return \VuFind\Db\Table\UserCard
     */
    public function getUserCardTable()
    {
        return $this->getDbTableManager()->get('UserCard');
    }

}
