<?php

namespace KnihovnyCz\ILS\Driver;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Factory for MultiBackend ILS driver.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class MultiBackendFactory implements FactoryInterface
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
        ?array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options passed to factory.');
        }
        $tableManager = $container->get(\VuFind\Db\Table\PluginManager::class);
        return new $requestedName(
            $container->get(\VuFind\Config\PluginManager::class),
            $container->get(\VuFind\Auth\ILSAuthenticator::class),
            $container->get(\VuFind\ILS\Driver\PluginManager::class),
            $tableManager->get(\KnihovnyCz\Db\Table\InstConfigs::class),
            $tableManager->get(\KnihovnyCz\Db\Table\InstSources::class),
            $container->get(\KnihovnyCz\ILS\Service\SolrIdResolver::class),
            $container->get(\KnihovnyCz\Date\Converter::class)
        );
    }
}
