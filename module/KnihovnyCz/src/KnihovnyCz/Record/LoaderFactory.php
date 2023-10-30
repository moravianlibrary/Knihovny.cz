<?php

namespace KnihovnyCz\Record;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Record loader factory.
 *
 * @category VuFind
 * @package  Record
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class LoaderFactory implements FactoryInterface
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
            throw new \Exception('Unexpected options passed to factory.');
        }
        $config = $container->get(\VuFind\Config\PluginManager::class);
        $search = $config->get('searches');
        $filterChildRecords = isset($search->ChildRecordFilters)
            && !empty($search->ChildRecordFilters->toArray());
        return new $requestedName(
            $container->get(\VuFindSearch\Service::class),
            $container->get(\VuFind\RecordDriver\PluginManager::class),
            $container->get(\VuFind\Record\Cache::class),
            $container->get(\VuFind\Record\FallbackLoader\PluginManager::class),
            $filterChildRecords
        );
    }
}
