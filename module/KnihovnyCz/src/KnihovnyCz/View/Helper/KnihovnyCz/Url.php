<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use function func_get_args;
use function func_num_args;

/**
 * Url
 *
 * @category VuFind
 * @package  KnihovnyCz\View_Helpers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Url extends \VuFind\View\Helper\Root\Url
{
    /**
     * Generates a url given the name of a route.
     *
     * @param string             $name               Name of the route
     * @param array              $params             Parameters for the link
     * @param array|\Traversable $options            Options for the route
     * @param bool               $reuseMatchedParams Whether to reuse matched
     * parameters
     *
     * @see \Laminas\Router\RouteInterface::assemble()
     *
     * @throws \Laminas\View\Exception\RuntimeException If no RouteStackInterface was provided
     * @throws \Laminas\View\Exception\RuntimeException If no RouteMatch was provided
     * @throws \Laminas\View\Exception\RuntimeException If RouteMatch didn't contain a matched
     * route name
     * @throws \Laminas\View\Exception\InvalidArgumentException If the params object was not an
     * array or Traversable object.
     *
     * @return self|string Url For the link href attribute
     */
    public function __invoke(
        $name = null,
        $params = [],
        $options = [],
        $reuseMatchedParams = false
    ) {
        // If argument list is empty, return object for method access:
        return func_num_args() == 0 ? $this : $this->removeLeadingSlash(parent::__invoke(...func_get_args()));
    }

    /**
     * Remove leading slash from URL if not root ("/")
     *
     * @param string $url url
     *
     * @return string url with removed leading slash
     */
    protected function removeLeadingSlash(string $url)
    {
        return ($url == '/') ? $url : rtrim($url, '/');
    }
}
