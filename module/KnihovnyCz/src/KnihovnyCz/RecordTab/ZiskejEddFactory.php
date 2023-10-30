<?php

namespace KnihovnyCz\RecordTab;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class ZiskejEddFactory
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejEddFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     DI container
     * @param string             $requestedName Service name
     * @param array|null         $options       Service options
     *
     * @return \KnihovnyCz\RecordTab\ZiskejEdd
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
    ): ZiskejEdd {
        return new $requestedName(
            $container->get(\VuFind\Auth\Manager::class),
            $container->get(\VuFind\ILS\Connection::class),
            $container->get(\Mzk\ZiskejApi\Api::class),
            $container->get(\VuFind\Cache\Manager::class)->getCache('object'),
            $container->get(\KnihovnyCz\Ziskej\ZiskejEdd::class)
        );
    }
}
