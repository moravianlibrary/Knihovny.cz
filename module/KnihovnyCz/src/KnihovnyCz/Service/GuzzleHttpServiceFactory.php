<?php

namespace KnihovnyCz\Service;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * KnihovnyCz Guzzle HTTP Service factory.
 *
 * @category VuFind
 * @package  Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GuzzleHttpServiceFactory implements FactoryInterface
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
        if (! empty($options)) {
            throw new \Exception('Unexpected options passed to factory.');
        }
        $proxyUrl = null;
        $nonProxyHosts = [];
        /**
         * Main configuration
         *
         * @var \Laminas\Config\Config $config
         */
        $config = $container->get(\VuFind\Config\PluginManager::class)->get('config');
        /**
         * Proxy configuration
         *
         * @var \Laminas\Config\Config $proxy
         */
        $proxy = $config->Proxy;
        if (isset($proxy->host) && ! empty($proxy->host)) {
            $host = $proxy->host;
            $port = $proxy->port ?? 80;
            $auth = $proxy->auth ?? null;
            $user = $proxy->user ?? null;
            $pass = $proxy->pass ?? null;
            if ($auth != null && $auth != 'basic') {
                throw new \Exception('Only basic auth is supported');
            }
            if ($auth == 'basic' && ! empty($user) && ! empty($pass)) {
                $user = urlencode($user);
                $pass = urlencode($pass);
                $proxyUrl = "http://$user:$pass@$host:$port/";
            } else {
                $proxyUrl = "http://$host:$port/";
            }
        }
        if (isset($proxy->non_proxy_host)) {
            $nonProxyHosts = $proxy->non_proxy_host->toArray();
        }
        $performanceLogger = null;
        if ($config->Http?->performance_log_enabled ?? false) {
            $performanceLogger = $container->get(\KnihovnyCz\Http\PerformanceLogger::class);
        }
        return new $requestedName($proxyUrl, $nonProxyHosts, $performanceLogger);
    }
}
