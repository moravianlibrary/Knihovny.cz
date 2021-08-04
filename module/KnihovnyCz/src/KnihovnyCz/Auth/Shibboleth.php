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