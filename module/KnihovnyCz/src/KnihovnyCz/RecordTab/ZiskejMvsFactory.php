<?php

namespace KnihovnyCz\RecordTab;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class ZiskejMvsFactory
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejMvsFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     DI container
     * @param string             $requestedName Service name
     * @param array|null         $options       Service options
     *
     * @return \KnihovnyCz\RecordTab\ZiskejMvs
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
    ): ZiskejMvs {
        return new $requestedName(
            $container->get(\VuFind\Auth\Manager::class),
            $container->get(\VuFind\ILS\Connection::class),
            $container->get(\Mzk\ZiskejApi\Api::class),
            $container->get(\VuFind\Cache\Manager::class)->getCache('object'),
            $container->get(\KnihovnyCz\Ziskej\ZiskejMvs::class)
        );
    }
}
