<?php

namespace KnihovnyCz\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use KnihovnyCz\Http\GuzzleClientAdapter;
use KnihovnyCz\Http\PerformanceLogger;

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
     * @var ?string $proxy proxy server to use
     */
    protected ?string $proxy;

    /**
     * List of host names with disabled proxy
     *
     * @var array
     */
    protected array $nonProxyHosts;

    /**
     * Performance logger
     *
     * @var \KnihovnyCz\Http\PerformanceLogger
     */
    protected ?PerformanceLogger $performanceLogger;

    /**
     * Options
     *
     * @var array
     */
    protected array $options;

    /**
     * GuzzleHttpService constructor.
     *
     * @param string?            $proxy             proxy server to use
     * @param array              $nonProxyHosts     list of host names to use without proxy
     * @param PerformanceLogger? $performanceLogger performance logger
     * @param array              $options           options
     */
    public function __construct(
        string|null $proxy = null,
        array $nonProxyHosts = [],
        ?PerformanceLogger $performanceLogger = null,
        array $options = []
    ) {
        $this->proxy = $proxy;
        $this->nonProxyHosts = $nonProxyHosts;
        $this->performanceLogger = $performanceLogger;
        $this->options = $options;
    }

    /**
     * Return a new HTTP client.
     *
     * @param array $config Configuration
     *
     * @return ClientInterface
     */
    public function createClient(array $config = []): ClientInterface
    {
        $stack = new HandlerStack(new CurlMultiHandler());
        $this->configureProxy($stack);
        $config['handler'] = $stack;
        if (isset($this->options['timeout'])) {
            $config['timeout'] = $this->options['timeout'];
        }
        $client = new \GuzzleHttp\Client($config);
        if ($this->performanceLogger != null) {
            $client = new GuzzleClientAdapter($client, $this->performanceLogger);
        }
        return $client;
    }

    /**
     * Configure proxy
     *
     * @param HandlerStack $stack $stack to configure
     *
     * @return void
     */
    protected function configureProxy(HandlerStack $stack)
    {
        if ($this->proxy == null) {
            return $stack;
        }
        $proxy = function (callable $handler) {
            return function (Request $request, array $options) use ($handler) {
                $host = $request->getUri()->getHost();
                if (!in_array($host, $this->nonProxyHosts)) {
                    $options['proxy'] = $this->proxy;
                }
                return $handler($request, $options);
            };
        };
        $stack->push($proxy);
    }
}
