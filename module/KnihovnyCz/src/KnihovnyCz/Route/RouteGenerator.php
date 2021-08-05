<?php
declare(strict_types=1);

/**
 * Class RouteGenerator
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\Route
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Route;

/**
 * Class RouteGenerator
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Route
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class RouteGenerator
{
    /**
     * Add a simple static route to the configuration.
     *
     * @param array  $config Configuration array to update
     * @param string $route  Controller/Action string representing route
     * @param string $url    Name of the route
     *
     * @return void
     */
    public function addStaticRoute(& $config, $route, $url)
    {
        [$controller, $action] = explode('/', $route);
        $routeName = str_replace('/', '-', strtolower($route));
        $config['router']['routes'][$routeName] = [
            'type' => 'Laminas\Router\Http\Literal',
            'options' => [
                'route'    => '/' . $url,
                'defaults' => [
                    'controller' => $controller,
                    'action'     => $action,
                ]
            ]
        ];
    }

    /**
     * Add simple static routes to the configuration.
     *
     * @param array $config Configuration array to update
     * @param array $routes Array of Controller/Action strings representing routes
     *
     * @return void
     */
    public function addStaticRoutes(& $config, $routes)
    {
        foreach ($routes as $name => $route) {
            $this->addStaticRoute($config, $route, $name);
        }
    }
}
