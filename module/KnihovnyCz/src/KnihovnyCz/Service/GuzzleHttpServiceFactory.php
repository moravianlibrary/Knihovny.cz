<?php

/**
 * KnihovnyCz Guzzle HTTP Service factory.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2019.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace KnihovnyCz\Service;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * KnihovnyCz Guzzle HTTP Service factory.
 *
 * @category VuFind
 * @package  Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GuzzleHttpServiceFactory  implements FactoryInterface
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
        $proxyUrl = null;
        /**
         * Main configuration
         *
         * @var \Laminas\Config\Config $config
         */
        $config = $container->get(\VuFind\Config\PluginManager::class)
            ->get('config');
        /**
         * Proxy configuration
         *
         * @var \Laminas\Config\Config $proxy
         */
        $proxy = $config->Proxy;
        if (isset($proxy->host)) {
            $host = $proxy->host;
            $port = $proxy->port ?? 80;
            $auth = $proxy->auth ?? null;
            $user = $proxy->user ?? null;
            $pass = $proxy->pass ?? null;
            if ($auth != null && $auth != 'basic') {
                throw new \Exception("Only basic auth is supported");
            }
            if ($auth == 'basic') {
                if ($user == null || $pass == null) {
                    throw new \Exception("No credentials for proxy");
                }
                $user = urlencode($user);
                $pass = urlencode($pass);
                $proxyUrl = "http://$user:$pass@$host:$port/";
            } else {
                $proxyUrl = "http://$host:$port/";
            }
        }
        return new $requestedName($proxyUrl);
    }
}
