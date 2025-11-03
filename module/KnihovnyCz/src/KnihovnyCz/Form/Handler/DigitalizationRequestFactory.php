<?php

declare(strict_types=1);

namespace KnihovnyCz\Form\Handler;

use KnihovnyCz\Record\Loader;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class DigitalizationRequestFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Form\Handler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class DigitalizationRequestFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     DI container
     * @param string             $requestedName Service name
     * @param array|null         $options       Service options
     *
     * @return \KnihovnyCz\Form\Handler\DigitalizationRequest
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
        $config = $container
            ->get(\VuFind\Config\PluginManager::class)
            ->get('digitalizationrequest');
        $host = $container
            ->get(\VuFind\Config\PluginManager::class)
            ->get('config')?->Site?->url ?? '';
        return new $requestedName(
            $config,
            $container->get('ViewHelperManager')->get('recordLinker'),
            $container->get(Loader::class),
            $container->get('ViewHelperManager')->get('serverUrl'),
            $host
        );
    }
}
