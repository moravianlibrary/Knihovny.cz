<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Row;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class UserListFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Db\Row
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserListFactory extends \VuFind\Db\Row\UserListFactory
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
        $config = $container->get(\VuFind\Config\PluginManager::class);
        $content = $config->get('content')->toArray();
        $searches = $config->get('searches')->toArray();
        $configValues = array_merge(
            $content['Inspiration']['content_block'] ?? [],
            $searches['HomePage']['content'] ?? []
        );
        $usedLists = [];
        foreach ($configValues as $value) {
            $parts = explode(':', $value);
            if ($parts[0] == 'UserList' && isset($parts[1])) {
                $usedLists[] = $parts[1];
            }
        }
        $usedLists = array_unique($usedLists);
        $userListRow = parent::__invoke($container, $requestedName, $options);
        $userListRow->setUsedLists($usedLists);
        return $userListRow;
    }
}
