<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use KnihovnyCz\Db\Row\User;

class AbstractBase extends \VuFind\Controller\AbstractBase
{
    /**
     * Get the user object if logged in, false otherwise.
     *
     * @return \KnihovnyCz\Db\Row\User|false
     */
    protected function getUser(): bool|User
    {
        return $this->getAuthManager()->isLoggedIn();
    }
}
