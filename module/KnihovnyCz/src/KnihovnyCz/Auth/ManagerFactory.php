<?php

namespace KnihovnyCz\Auth;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Authentication Manager factory.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ManagerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException&\Throwable if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        // Load dependencies:
        $config = $container->get(\VuFind\Config\PluginManager::class)
            ->get('config');
        $userService = $container->get(\VuFind\Db\Service\PluginManager::class)
            ->get(\VuFind\Db\Service\UserServiceInterface::class);
        $sessionManager = $container->get(\Laminas\Session\SessionManager::class);
        $pm = $container->get(\VuFind\Auth\PluginManager::class);
        $cookies = $container->get(\VuFind\Cookie\CookieManager::class);
        $csrf = $container->get(\VuFind\Validator\CsrfInterface::class);
        $loginTokenManager = $container->get(\VuFind\Auth\LoginTokenManager::class);
        $ils = $container->get(\VuFind\ILS\Connection::class);
        $userSettingsService = $container->get(\KnihovnyCz\Db\Service\UserSettingsService::class);
        $viewRenderer = $container->get('ViewRenderer');

        // Build the object and make sure account credentials haven't expired:
        $manager = new $requestedName(
            $config,
            $userService,
            $sessionManager,
            $pm,
            $cookies,
            $csrf,
            $loginTokenManager,
            $ils,
            $userSettingsService,
            $viewRenderer
        );
        $manager->checkForExpiredCredentials();
        return $manager;
    }
}
