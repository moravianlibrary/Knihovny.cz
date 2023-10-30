<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use KnihovnyCz\Db\Row\User;

/**
 * Class AbstractBase
 *
 * @category VuFind
 * @package  KnihovnyCz\Controller
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AbstractBase extends \VuFind\Controller\AbstractBase
{
    /**
     * Get the user object if logged in, false otherwise.
     *
     * @return User|false
     */
    protected function getUser(): false|User
    {
        /**
         * User model
         *
         * @var User|false $user
         */
        $user = $this->getAuthManager()->isLoggedIn();
        return $user;
    }
}
