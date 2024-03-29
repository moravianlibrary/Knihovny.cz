<?php

declare(strict_types=1);

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
class RouteGenerator extends \VuFind\Route\RouteGenerator
{
    /**
     * Add record route to the configuration.
     *
     * @param array  $config     Configuration array to update
     * @param string $routeBase  Base name to use for routes
     * @param string $controller Controller to point routes toward
     * @param string $route      Route URL part
     *
     * @return void
     */
    protected function addRecordRouteKnihovnyCz(
        array &$config,
        string $routeBase,
        string $controller,
        string $route
    ): void {
        // catch-all "tab" route:
        $config['router']['routes'][$routeBase] = [
            'type'    => 'Laminas\Router\Http\Segment',
            'options' => [
                'route'    => '/' . $route . '/[:id[/[:tab]]]',
                'constraints' => [
                    'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                ],
                'defaults' => [
                    'controller' => $controller,
                    'action'     => 'Home',
                ],
            ],
        ];
        // special non-tab actions that each need their own route:
        foreach (self::$nonTabRecordActions as $action) {
            $config['router']['routes'][$routeBase . '-' . strtolower($action)] = [
                'type'    => 'Laminas\Router\Http\Segment',
                'options' => [
                    'route'    => '/' . $route . '/[:id]/' . $action,
                    'constraints' => [
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => $controller,
                        'action'     => $action,
                    ],
                ],
            ];
        }
    }

    /**
     * Add record routes to the configuration.
     *
     * @param array $config Configuration array to update
     * @param array $routes Associative array (route base name => controller) of
     * routes to add.
     *
     * @return void
     */
    public function addRecordRoutes(&$config, $routes)
    {
        foreach ($routes as $routeBase => $routeData) {
            $this->addRecordRouteKnihovnyCz(
                $config,
                $routeBase,
                $routeData[0],
                $routeData[1]
            );
        }
    }

    /**
     * Add a simple static route to the configuration.
     *
     * @param array  $config Configuration array to update
     * @param string $route  Controller/Action string representing route
     * @param string $url    Name of the route
     *
     * @return void
     */
    protected function addStaticRouteKnihovnyCz(
        array &$config,
        string $route,
        string $url
    ): void {
        [$controller, $action] = explode('/', $route);
        $routeName = str_replace('/', '-', strtolower($route));
        $config['router']['routes'][$routeName] = [
            'type' => 'Laminas\Router\Http\Literal',
            'options' => [
                'route'    => '/' . $url,
                'defaults' => [
                    'controller' => $controller,
                    'action'     => $action,
                ],
            ],
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
    public function addStaticRoutes(&$config, $routes)
    {
        foreach ($routes as $name => $route) {
            $this->addStaticRouteKnihovnyCz($config, $route, $name);
        }
    }
}
