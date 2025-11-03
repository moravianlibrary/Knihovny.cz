<?php

namespace KnihovnyCz\Service;

use KnihovnyCz\Http\PerformanceLogger;
use Laminas\Http\PhpEnvironment\Request;

/**
 * Knihovny.cz HttpService
 *
 * @category KnihovnyCz
 * @package  Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
class HttpService extends \VuFindHttp\HttpService
{
    /**
     * Performance logger
     *
     * @var \KnihovnyCz\Http\PerformanceLogger
     */
    protected ?PerformanceLogger $performanceLogger;

    /**
     * Constructor.
     *
     * @param array                  $proxyConfig Proxy configuration
     * @param array                  $defaults    Default HTTP options
     * @param array                  $config      Other configuration
     * @param PerformanceLogger|null $perfLogger  Performance logger
     */
    public function __construct(
        array $proxyConfig = [],
        array $defaults = [],
        array $config = [],
        ?PerformanceLogger $perfLogger = null
    ) {
        parent::__construct($proxyConfig, $defaults, $config);
        $this->performanceLogger = $perfLogger;
    }

    /**
     * Return a new HTTP client.
     *
     * @param string $url     Target URL
     * @param string $method  Request method
     * @param float  $timeout Request timeout in seconds
     *
     * @return \Laminas\Http\Client
     */
    public function createClient(
        $url = null,
        $method = \Laminas\Http\Request::METHOD_GET,
        $timeout = null
    ) {
        $client = parent::createClient($url, $method, $timeout);
        if ($this->performanceLogger != null) {
            $wrapper = new \KnihovnyCz\Http\LoggingHttpAdapter($client->getAdapter(), $this->performanceLogger);
            $client->setAdapter($wrapper);
        }
        return $client;
    }

    /**
     * Set proxy options in a Curl adapter.
     *
     * @param \Laminas\Http\Client\Adapter\Curl $adapter Adapter to configure
     *
     * @return void
     */
    protected function setCurlProxyOptions($adapter)
    {
        parent::setCurlProxyOptions($adapter);
        if (
            !empty($this->proxyConfig['proxy_user'])
            && !empty($this->proxyConfig['proxy_pass'])
        ) {
            $adapter
                ->setCurlOption(CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            $adapter
                ->setCurlOption(
                    CURLOPT_PROXYUSERNAME,
                    $this->proxyConfig['proxy_user']
                );
            $adapter
                ->setCurlOption(
                    CURLOPT_PROXYPASSWORD,
                    $this->proxyConfig['proxy_pass']
                );
        }
        if (!empty($this->proxyConfig['non_proxy_host'])) {
            $nonProxyHosts = implode(',', $this->proxyConfig['non_proxy_host']);
            $adapter
                ->setCurlOption(CURLOPT_NOPROXY, $nonProxyHosts);
        }
    }
}
