<?php

namespace KnihovnyCz\Auth;

use VuFind\Auth\ILSAuthenticator;
use VuFind\Auth\Shibboleth as Base;
use VuFind\Auth\Shibboleth\ConfigurationLoaderInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\UserCardServiceInterface;
use VuFind\Db\Service\UserServiceInterface;
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
    protected const MULTIVALUED_ATTRIBUTES = [ 'mail' ];

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
     * @param \Laminas\Session\ManagerInterface    $sessionManager      Session manager
     * @param ConfigurationLoaderInterface         $configurationLoader Configuration loader
     * @param \Laminas\Http\PhpEnvironment\Request $request             Http request object
     * @param \VuFind\Auth\ILSAuthenticator        $ilsAuthenticator    ILS Authenticator
     */
    public function __construct(
        \Laminas\Session\ManagerInterface $sessionManager,
        ConfigurationLoaderInterface $configurationLoader,
        \Laminas\Http\PhpEnvironment\Request $request,
        ILSAuthenticator $ilsAuthenticator
    ) {
        parent::__construct($sessionManager, $configurationLoader, $request, $ilsAuthenticator);
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
     * @return UserEntityInterface Object representing logged-in user.
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
                'No eduPersonUniqueId attribute '
                . "({$shib['edu_person_unique_id']}) "
                . 'present in request: ' . print_r($details, true)
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
         * @var UserServiceInterface $userService
         */
        $userService = $this->getUserService();
        $user = $userService->getUserByField('edu_person_unique_id', $eduPersonUniqueId);
        if ($user == null) {
            $user = $userService->getUserByField('username', $eduPersonUniqueId);
        }
        // lookup by eduPersonPrincipalName for backward compatibility
        $lookupByEppn = $shib['lookupByEduPersonPrincipalName'] ?? false;
        if ($user == null && $lookupByEppn && isset($shib['eppn'])) {
            $eppn = $this->getAttribute($request, $shib['eppn']);
            $card = $this->getUserCardByEppnWithoutEpui($eppn);
            if ($card != null) {
                $card->setEduPersonUniqueId($eduPersonUniqueId);
                $card->save();
                $user = $card->getUser();
                $user->setUsername($eduPersonUniqueId);
            }
        }
        if ($user == null) {
            $user = $userService->createEntityForUsername($eduPersonUniqueId);
            // Failing to initialize this here can cause Laminas\Db errors in
            // the VuFind\Auth\Shibboleth and VuFind\Auth\ILS integration tests.
            $user->setHasUserProvidedEmail(false);
        }

        // Has the user configured attributes to use for populating the user table?
        foreach ($this->attribsToCheck as $attribute) {
            if (isset($shib[$attribute])) {
                $value = $this->getAttribute($request, $shib[$attribute]);
                if ($attribute == 'email' && $value != null) {
                    $userService->updateUserEmail($user, $value);
                } elseif ($attribute == 'cat_username' && isset($shib['prefix'])) {
                    $user->setCatUsername($shib['prefix'] . '.' . ($value ?? ''));
                } else {
                    $this->setUserValueByField($user, $attribute, $value ?? '');
                }
            }
        }
        if (isset($shib['prefix'])) {
            $user->setHomeLibrary($shib['prefix']);
        }

        $this->storeShibbolethSession($request);

        // Save and return the user object:
        $userService->persistEntity($user);
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
                $card->setEduPersonUniqueId($eduPersonUniqueId);
            }
        }
        // Is library card already connected to another user? If so, merge the
        // two users.
        if ($card != null && $card->getUser()->getId() != $connectingUser->getId()) {
            $this->getUserTable()->merge($card->getUser(), $connectingUser);
            $card->setUser($connectingUser);
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
            foreach ($this->getUserCardService()->getLibraryCards($connectingUser) as $libCard) {
                $institution = explode(
                    '.',
                    $libCard->getCatUsername()
                )[0];
                if (
                    $institution == $prefix
                    && $eduPersonUniqueId != $libCard->getEduPersonUniqueId()
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
            $card = $this->getUserCardService()->getOrCreateLibraryCard($connectingUser);
            $card->setCreated(new \DateTime());
            $card->setEduPersonUniqueId($eduPersonUniqueId);
            $card->setCardName($prefix);
            $card->setHomeLibrary($prefix);
        }
        // update library card
        $card->setCatUsername($username);
        if (isset($shib['eppn'])) {
            $card->setEppn($this->getAttribute($request, $shib['eppn']));
        }
        $this->getUserCardService()->persistEntity($card);
    }

    /**
     * Get access to the user table.
     *
     * @return \KnihovnyCz\Db\Table\UserCard
     */
    public function getUserCardTable(): \KnihovnyCz\Db\Table\UserCard
    {
        return $this->getDbTableManager()->get('UserCard');
    }

    /**
     * Extract attribute from request.
     *
     * @param \Laminas\Http\PhpEnvironment\Request $request   Request object
     * @param string                               $attribute Attribute name
     *
     * @return ?string attribute value
     */
    protected function getAttribute($request, $attribute): ?string
    {
        $value = parent::getAttribute($request, $attribute);
        if ($value == null || !in_array($attribute, self::MULTIVALUED_ATTRIBUTES)) {
            return $value;
        }
        $values = explode(';', $value);
        return $values[0];
    }

    /**
     * Return library card by eduPersonPrincipalName without eduPersonUniqueId.
     *
     * @param string|null $eppn eduPersonPrincipalName
     *
     * @return \KnihovnyCz\Db\Row\UserCard|null
     */
    protected function getUserCardByEppnWithoutEpui(string|null $eppn): \KnihovnyCz\Db\Row\UserCard|null
    {
        if (empty($eppn)) {
            return null;
        }
        $card = $this->getUserCardTable()->getByEduPersonPrincipalName($eppn);
        if ($card?->getEduPersonUniqueId() == null) {
            return $card;
        }
        return null;
    }

    /**
     * Get access to the user card service.
     *
     * @return UserCardServiceInterface
     */
    protected function getUserCardService(): UserCardServiceInterface
    {
        return $this->getDbService(UserCardServiceInterface::class);
    }

    /**
     * Get access to the user table.
     *
     * @return \KnihovnyCz\Db\Table\User
     */
    protected function getUserTable(): \KnihovnyCz\Db\Table\User
    {
        return $this->getDbTable('User');
    }
}
