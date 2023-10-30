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
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        // Set up configuration:
        $config = $container->get(\VuFind\Config\PluginManager::class)
            ->get('config');
        try {
            // Check if the catalog wants to hide the login link, and override
            // the configuration if necessary.
            $catalog = $container->get(\VuFind\ILS\Connection::class);
            if ($catalog->loginIsHidden()) {
                $config = new \Laminas\Config\Config($config->toArray(), true);
                $config->Authentication->hideLogin = true;
                $config->setReadOnly();
            }
        } catch (\Exception $e) {
            // Ignore exceptions; if the catalog is broken, throwing an exception
            // here may interfere with UI rendering. If we ignore it now, it will
            // still get handled appropriately later in processing.
            error_log($e->getMessage());
        }

        // Load remaining dependencies:
        $userTable = $container->get(\VuFind\Db\Table\PluginManager::class)
            ->get('user');
        $sessionManager = $container->get(\Laminas\Session\SessionManager::class);
        $pm = $container->get(\VuFind\Auth\PluginManager::class);
        $cookies = $container->get(\VuFind\Cookie\CookieManager::class);
        $csrf = $container->get(\VuFind\Validator\CsrfInterface::class);
        $restorer = $container->get(\KnihovnyCz\Service\UserSettingsService::class);

        // Build the object and make sure account credentials haven't expired:
        $manager = new $requestedName(
            $config,
            $userTable,
            $sessionManager,
            $pm,
            $cookies,
            $csrf,
            $restorer
        );
        $manager->checkForExpiredCredentials();
        return $manager;
    }
}
