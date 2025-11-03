<?php

namespace KnihovnyCz\Auth;

use KnihovnyCz\Db\Service\UserSettingsService as UserSettingsService;
use Laminas\Session\SessionManager;
use Laminas\View\Renderer\RendererInterface;
use VuFind\Auth\LoginTokenManager;
use VuFind\Auth\Manager as Base;
use VuFind\Auth\PluginManager;
use VuFind\Config\Config;
use VuFind\Cookie\CookieManager;
use VuFind\Db\Row\User as UserRow;
use VuFind\Db\Service\UserServiceInterface;
use VuFind\ILS\Connection;
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
     * Restorer to load saved user settings to session
     *
     * @var UserSettingsService
     */
    protected $userSettingsService;

    /**
     * Constructor
     *
     * @param Config               $config            VuFind configuration
     * @param UserServiceInterface $userService       User database service
     * @param SessionManager       $sessionManager    Session manager
     * @param PluginManager        $pm                Authentication plugin manager
     * @param CookieManager        $cookieManager     Cookie manager
     * @param CsrfInterface        $csrf              CSRF validator
     * @param LoginTokenManager    $loginTokenManager Login Token manager
     * @param Connection           $ils               ILS Connection
     * @param UserSettingsService  $userSettings      Restorer
     * @param RendererInterface    $viewRenderer      View renderer
     */
    public function __construct(
        Config $config,
        UserServiceInterface $userService,
        SessionManager $sessionManager,
        PluginManager $pm,
        CookieManager $cookieManager,
        CsrfInterface $csrf,
        LoginTokenManager $loginTokenManager,
        Connection $ils,
        UserSettingsService $userSettings,
        RendererInterface $viewRenderer
    ) {
        parent::__construct(
            $config,
            $userService,
            $userService,
            $sessionManager,
            $pm,
            $cookieManager,
            $csrf,
            $loginTokenManager,
            $ils,
            $viewRenderer
        );
        $this->userSettingsService = $userSettings;
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
    public function logout($url, $destroy = true, $extLogout = true): string
    {
        $extLogoutUrl = parent::logout($url, $destroy);
        return ($extLogout) ? $extLogoutUrl : $url;
    }

    /**
     * Updates the user information in the session.
     *
     * @param UserRow $user User object to store in the session
     *
     * @return void
     */
    public function updateSession($user): void
    {
        parent::updateSession($user);
        // add user data to session even if privacy mode is disabled
        $this->userSession->addUserDataToSession($user);
        $this->userSettingsService->restore();
    }
}
