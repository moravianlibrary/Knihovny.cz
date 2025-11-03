<?php

namespace KnihovnyCz\Service;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * KnihovnyCz HTTP Service factory.
 *
 * @category KnihovnyCz
 * @package  Service
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class HttpServiceFactory implements FactoryInterface
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
        ?array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options passed to factory.');
        }
        $config = $container->get(\VuFind\Config\PluginManager::class)
            ->get('config');
        $options = [];
        if (isset($config->Proxy->host)) {
            $options['proxy_host'] = $config->Proxy->host;
            if (isset($config->Proxy->port)) {
                $options['proxy_port'] = $config->Proxy->port;
            }
            if (isset($config->Proxy->type)) {
                $options['proxy_type'] = $config->Proxy->type;
            }
            foreach (['auth', 'user', 'pass'] as $key) {
                if (isset($config->Proxy->{$key})) {
                    $options['proxy_' . $key] = $config->Proxy->{$key};
                }
            }
            if (isset($config->Proxy->non_proxy_host)) {
                $options['non_proxy_host'] = $config->Proxy
                    ->non_proxy_host->toArray();
            }
        }
        $defaults = isset($config->Http)
            ? $config->Http->toArray() : [];
        $performanceLogger = null;
        if ($defaults['performance_log_enabled'] ?? false) {
            $performanceLogger = $container->get(\KnihovnyCz\Http\PerformanceLogger::class);
        }
        return new $requestedName($options, $defaults, [], $performanceLogger);
    }
}
