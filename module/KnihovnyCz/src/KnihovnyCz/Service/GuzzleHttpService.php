<?php

namespace KnihovnyCz\Service;

use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;

/**
 * Class GuzzleHttpService
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GuzzleHttpService
{
    /**
     * Configuration
     *
     * @param ?string $proxy proxy server to use
     */
    protected ?string $proxy;

    /**
     * GuzzleHttpService constructor.
     *
     * @param string $proxy proxy server to use
     */
    public function __construct($proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * Return a new HTTP client.
     *
     * @param array $config Configuration
     *
     * @return Client
     */
    public function createClient($config = [])
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        if ($this->proxy != null) {
            $stack->push(self::addProxy($this->proxy));
        }
        $config['handler'] = $stack;
        return new \GuzzleHttp\Client($config);
    }

    /**
     * Configure proxy
     *
     * @param string $proxy proxy server to use
     *
     * @return \Closure
     */
    public static function addProxy($proxy)
    {
        return function (callable $handler) use ($proxy) {
            return function ($request, array $options) use ($handler, $proxy) {
                $options['proxy'] = $proxy;
                return $handler($request, $options);
            };
        };
    }
}
