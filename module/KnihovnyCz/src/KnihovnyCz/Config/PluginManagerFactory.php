<?php

namespace KnihovnyCz\Config;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class PluginManagerFactory
 *
 * @category VuFind
 * @package  KnihovnyCz\Config
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class PluginManagerFactory extends \VuFind\Config\PluginManagerFactory
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
        /**
         * Config plugin manager
         *
         * @var PluginManager $service
         */
        $service = parent::__invoke($container, $requestedName, $options);
        $service->addAbstractFactory(\KnihovnyCz\Config\PluginFactory::class);
        return $service;
    }
}
