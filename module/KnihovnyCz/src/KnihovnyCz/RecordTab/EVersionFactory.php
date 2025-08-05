<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordTab;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class EVersion Factory
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class EVersionFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     DI container
     * @param string             $requestedName Service name
     * @param array|null         $options       Service options
     *
     * @return EVersion
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): EVersion {
        return new $requestedName(
            $container->get(\KnihovnyCz\Service\PalmknihyApiService::class),
        );
    }
}
