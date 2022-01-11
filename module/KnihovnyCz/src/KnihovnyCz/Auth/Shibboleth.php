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
 * @link     https://vufind.org Main Page
 */
namespace KnihovnyCz\Auth;

use VuFind\Auth\Shibboleth as Base;
use VuFind\Auth\Shibboleth\ConfigurationLoaderInterface;
use VuFind\Exception\Auth as AuthException;

/**
 * Shibboleth authentication module.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Shibboleth extends Base
{
    /**
     * Check for duplicities in library cards - only one library card for
     * institution
     *
     * @var bool
     */
    protected $checkDuplicateInstitutions = true;

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
    public function __construct(
        \Laminas\Session\ManagerInterface $sessionManager,
        ConfigurationLoaderInterface $configurationLoader,
        \Laminas\Http\PhpEnvironment\Request $request
    ) {
        parent::__construct($sessionManager, $configurationLoader, $request);
    }

    /**
     * Set configuration.
     *
     * @param \Laminas\Config\Config $config Configuration to set
     *
     * @return void
     */
    public function setConfig($config)
    {
        parent::setConfig($config);
        $this->checkDuplicateInstitutions = $this->config->Shibboleth
            ->check_duplicate_institutions ?? true;
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
        $eduPersonUniqueId = $this->getAttribute(
            $request,
            $shib['edu_person_unique_id']
        );
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
        /**
         * User db table
         *
         * @var \KnihovnyCz\Db\Table\User $userTable
         */
        $userTable = $this->getUserTable();
        $user = $userTable->getByEduPersonUniqueId($eduPersonUniqueId);
        if ($user == null) {
            $user = $userTable->getByUsername($eduPersonUniqueId, false);
        }
        // lookup by eduPersonPrincipalName for backward compatibility
        $lookupByEppn = $shib['lookupByEduPersonPrincipalName'] ?? false;
        if ($user == null && $lookupByEppn && isset($shib['eppn'])) {
            $eppn = $this->getAttribute($request, $shib['eppn']);
            $card = $this->getUserCardByEppnWithoutEpui($eppn);
            if ($card != null) {
                $card->edu_person_unique_id = $eduPersonUniqueId;
                $card->save();
                $user = $userTable->getById($card->user_id);
                $user->username = $eduPersonUniqueId;
            }
        }
        if ($user == null) {
            $user = $userTable->createRow();
            $user->created = date('Y-m-d H:i:s');
            $user->username = $eduPersonUniqueId;
            // Failing to initialize this here can cause Laminas\Db errors in
            // the VuFind\Auth\Shibboleth and VuFind\Auth\ILS integration tests.
            $user->user_provided_email = 0;
        }

        // Has the user configured attributes to use for populating the user table?
        foreach ($this->attribsToCheck as $attribute) {
            if (isset($shib[$attribute])) {
                $value = $this->getAttribute($request, $shib[$attribute]);
                if ($attribute == 'email' && $value != null) {
                    $user->updateEmail($value);
                } elseif ($attribute == 'cat_username' && isset($shib['prefix'])) {
                    $user->cat_username = $shib['prefix'] . '.' . ($value ?? '');
                } else {
                    $user->$attribute = $value ?? '';
                }
            }
        }
        if (isset($shib['prefix'])) {
            $user->home_library = $shib['prefix'];
        }

        $this->storeShibbolethSession($request);

        // modification for GDPR - do not store last name, first name and email
        // in database
        $userInfo = [];
        $userInfo['firstname'] = $user->firstname;
        $userInfo['lastname'] = $user->lastname;
        $userInfo['email'] = $user->email;
        $userInfo['safeLogout'] = $shib['safeLogout'] ?? 'global';
        $session = new \Laminas\Session\Container(
            'Account',
            $this->sessionManager
        );
        /* @phpstan-ignore-next-line */
        $session->userInfo = $userInfo;

        // Save and return the user object:
        $user->save();
        // create library card
        $this->connectLibraryCard($request, $user);
        return $user;
    }

    /**
     * Connect user authenticated by Shibboleth to library card.
     *
     * @param \Laminas\Http\PhpEnvironment\Request $request        Request object
     * containing account credentials.
     * @param \KnihovnyCz\Db\Row\User              $connectingUser Connect newly
     * created library card to this user.
     *
     * @return void
     */
    public function connectLibraryCard($request, $connectingUser)
    {
        $entityId = $this->getCurrentEntityId($request);
        $shib = $this->getConfigurationLoader()->getConfiguration($entityId);
        $eduPersonUniqueId = $this->getAttribute(
            $request,
            $shib['edu_person_unique_id']
        );
        if (empty($eduPersonUniqueId)) {
            throw new \VuFind\Exception\LibraryCard(
                'Missing eduPersonUniqueId attribute'
            );
        }
        $card = $this->getUserCardTable()
            ->getByEduPersonUniqueId($eduPersonUniqueId);
        // lookup by eduPersonPrincipalName for backward compatibility
        $lookupByEppn = $shib['lookupByEduPersonPrincipalName'] ?? false;
        if ($card == null && $lookupByEppn) {
            $eppn = $this->getAttribute($request, $shib['eppn']);
            $card = $this->getUserCardByEppnWithoutEpui($eppn);
            if ($card != null) {
                $card->edu_person_unique_id = $eduPersonUniqueId;
            }
        }
        // Is library card already connected to another user? If so, merge the
        // two users.
        if ($card != null && $card->user_id != $connectingUser->id) {
            /**
             * User model
             *
             * @var \KnihovnyCz\Db\Row\User $user
             */
            $user = $this->getUserTable()->getById($card->user_id);
            /**
             * User db table
             *
             * @var \KnihovnyCz\Db\Table\User $userTable
             */
            $userTable = $this->getUserTable();
            $userTable->merge($user, $connectingUser);
            $card->user_id = $connectingUser->id;
        }
        $username = $this->getAttribute($request, $shib['cat_username']);
        $prefix = $shib['prefix'] ?? '';
        if (!empty($prefix)) {
            $username = $shib['prefix'] . '.' . $username;
        }
        // check for duplicity - only one library card for institution
        if ($this->checkDuplicateInstitutions) {
            /**
             * User card model
             *
             * @var \KnihovnyCz\Db\Row\UserCard $libCard
             */
            foreach ($connectingUser->getLibraryCards() as $libCard) {
                $institution = explode(
                    '.',
                    $libCard->cat_username
                )[0];
                if ($institution == $prefix
                    && $eduPersonUniqueId != $libCard->edu_person_unique_id
                ) {
                    throw new \VuFind\Exception\LibraryCard(
                        'Another library card with the same institution is '
                        . 'already connected to your account'
                    );
                }
            }
        }
        if ($card == null) {
            /**
             * User model
             *
             * @var \KnihovnyCz\Db\Row\UserCard $card
             */
            $card = $this->getUserCardTable()->createRow();
            $card->created = date('Y-m-d H:i:s');
            $card->user_id = $connectingUser->id;
            $card->edu_person_unique_id = $eduPersonUniqueId;
            $card->card_name = $prefix;
            $card->home_library = $prefix;
        }
        // update library card
        $card->cat_username = $username;
        if (isset($shib['eppn'])) {
            $card->eppn = $this->getAttribute($request, $shib['eppn']);
        }
        $card->save();
    }

    /**
     * Get access to the user table.
     *
     * @return \KnihovnyCz\Db\Table\UserCard
     */
    public function getUserCardTable()
    {
        return $this->getDbTableManager()->get('UserCard');
    }

    /**
     * Return library card by eduPersonPrincipalName without eduPersonUniqueId.
     *
     * @param string $eppn eduPersonPrincipalName
     *
     * @return \KnihovnyCz\Db\Row\UserCard|null
     */
    protected function getUserCardByEppnWithoutEpui($eppn)
    {
        if ($eppn == null) {
            return null;
        }
        $card = $this->getUserCardTable()
            ->getByEduPersonPrincipalName($eppn);
        if (!isset($card->edu_person_unique_id)
            || $card->edu_person_unique_id == null
        ) {
            return $card;
        }
        return null;
    }
}
