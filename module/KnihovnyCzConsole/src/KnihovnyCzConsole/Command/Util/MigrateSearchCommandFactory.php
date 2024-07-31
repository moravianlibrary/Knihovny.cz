<?php

namespace KnihovnyCzConsole\Command\Util;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class MigrateSearchCommandFactory
 *
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class MigrateSearchCommandFactory implements FactoryInterface
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
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        $tableManager = $container->get(\VuFind\Db\Table\PluginManager::class);
        $resultsManager = $container->get(\VuFind\Search\Results\PluginManager::class);
        return new $requestedName(
            $tableManager->get(\KnihovnyCz\Db\Table\Search::class),
            $resultsManager,
            ...($options ?? [])
        );
    }
}
