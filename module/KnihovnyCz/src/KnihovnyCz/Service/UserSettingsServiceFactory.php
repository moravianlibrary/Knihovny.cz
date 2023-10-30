<?php

namespace KnihovnyCz\Service;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Container;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Factory for user settings restorer
 *
 * @category VuFind
 * @package  Authentication
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class UserSettingsServiceFactory implements FactoryInterface
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
        $session = new Container(
            'Account',
            $container->get(\Laminas\Session\SessionManager::class)
        );
        $config = $container->get(\VuFind\Config\PluginManager::class);
        $memory = $container->get(\VuFind\Search\Memory::class);
        $settingsTable = $container->get(\VuFind\Db\Table\PluginManager::class)
            ->get('UserSettings');

        // Build the object and make sure account credentials haven't expired:
        $restorer = new $requestedName(
            $session,
            $config,
            $memory,
            $settingsTable
        );
        return $restorer;
    }
}
