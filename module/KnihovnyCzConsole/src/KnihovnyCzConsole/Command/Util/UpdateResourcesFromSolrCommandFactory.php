<?php

namespace KnihovnyCzConsole\Command\Util;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class UpdateResourcesFromSolrCommandFactory
 *
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UpdateResourcesFromSolrCommandFactory implements FactoryInterface
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
        $tableManager = $container->get(\VuFind\Db\Table\PluginManager::class);
        $recordLoader = $container->get(\KnihovnyCz\Record\Loader::class);
        $converter = $container->get(\VuFind\Date\Converter::class);
        return new $requestedName(
            $tableManager->get(\KnihovnyCz\Db\Table\Resource::class),
            $recordLoader,
            $converter,
            ...($options ?? [])
        );
    }
}
