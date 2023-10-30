<?php

namespace KnihovnyCz\Auth;

use KnihovnyCz\Service\UserSettingsService as Restorer;
use Laminas\Config\Config;
use Laminas\Session\SessionManager;
use VuFind\Auth\Manager as Base;
use VuFind\Auth\PluginManager;
use VuFind\Cookie\CookieManager;
use VuFind\Db\Row\User;
use VuFind\Db\Row\User as UserRow;
use VuFind\Db\Table\User as UserTable;
use VuFind\Validator\CsrfInterface;

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
     * Restorer to load saved user settings to sesssion
     *
     * @var Restorer
     */
    protected $restorer;

    /**
     * Constructor
     *
     * @param Config         $config         VuFind configuration
     * @param UserTable      $userTable      User table gateway
     * @param SessionManager $sessionManager Session manager
     * @param PluginManager  $pm             Authentication plugin manager
     * @param CookieManager  $cookieManager  Cookie manager
     * @param CsrfInterface  $csrf           CSRF validator
     * @param Restorer       $restorer       Restorer
     */
    public function __construct(
        Config $config,
        UserTable $userTable,
        SessionManager $sessionManager,
        PluginManager $pm,
        CookieManager $cookieManager,
        CsrfInterface $csrf,
        Restorer $restorer
    ) {
        parent::__construct(
            $config,
            $userTable,
            $sessionManager,
            $pm,
            $cookieManager,
            $csrf
        );
        $this->restorer = $restorer;
    }

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

    /**
     * Updates the user information in the session.
     *
     * @param UserRow $user User object to store in the session
     *
     * @return void
     */
    public function updateSession($user)
    {
        parent::updateSession($user);
        $this->restorer->restore();
    }
}
