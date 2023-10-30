<?php

namespace KnihovnyCz\ContentBlock;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Class AbstractDbAwaredRecordIdsFactory
 *
 * @category VuFind
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AbstractDbAwaredRecordIdsFactory implements FactoryInterface
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
     * @throws \Exception if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }

        $contentBlock = new $requestedName(
            $container->get(\VuFind\Db\Table\PluginManager::class),
            $container->get(\VuFind\Record\Loader::class),
            $container->get('ViewHelperManager')->get('url'),
            $container->get(\VuFind\Search\Options\PluginManager::class),
            $container->get(\VuFind\RecordDriver\PluginManager::class)
        );

        // Populate cache storage if a setCacheStorage method is present:
        if (method_exists($contentBlock, 'setCacheStorage')) {
            $contentBlock->setCacheStorage(
                $container->get(\VuFind\Cache\Manager::class)->getCache('object')
            );
        }
        return $contentBlock;
    }
}
