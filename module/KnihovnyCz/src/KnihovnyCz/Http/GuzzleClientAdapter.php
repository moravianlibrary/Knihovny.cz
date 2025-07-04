<?php

namespace KnihovnyCz\Http;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class LoggingHttpAdapter
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GuzzleClientAdapter implements \GuzzleHttp\ClientInterface, \Psr\Http\Client\ClientInterface
{
    /**
     * Implementation to delegate to
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected \GuzzleHttp\ClientInterface $delegate;

    /**
     * Performance logger
     *
     * @var \KnihovnyCz\Http\PerformanceLogger
     */
    protected ?PerformanceLogger $performanceLogger;

    /**
     * Create Guzzle client adapter
     *
     * @param \GuzzleHttp\ClientInterface $delegate   delegate
     * @param PerformanceLogger           $perfLogger performance logger
     */
    public function __construct(\GuzzleHttp\ClientInterface $delegate, PerformanceLogger $perfLogger = null)
    {
        $this->delegate = $delegate;
        $this->performanceLogger = $perfLogger;
    }

    /**
     * Send an HTTP request.
     *
     * @param RequestInterface $request Request to send
     * @param array            $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->delegate->send($request, $this->wrapOptions($options));
    }

    /**
     * Asynchronously send an HTTP request.
     *
     * @param RequestInterface $request Request to send
     * @param array            $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return PromiseInterface
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return $this->delegate->sendAsync($request, $this->wrapOptions($options));
    }

    /**
     * Create and send an HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string              $method  HTTP method.
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function request($method, $uri, array $options = []): ResponseInterface
    {
        return $this->delegate->request($method, $uri, $this->wrapOptions($options));
    }

    /**
     * Create and send an asynchronous HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string              $method  HTTP method
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     *
     * @return PromiseInterface
     */
    public function requestAsync($method, $uri, array $options = []): PromiseInterface
    {
        return $this->delegate->requestAsync($method, $uri, $this->wrapOptions($options));
    }

    /**
     * Get a client configuration option.
     *
     * These options include default request options of the client, a "handler"
     * (if utilized by the concrete client), and a "base_uri" if utilized by
     * the concrete client.
     *
     * @param string|null $option The config option to retrieve.
     *
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return $this->delegate->getConfig($option);
    }

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request request
     *
     * @return ResponseInterface
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->send($request);
    }

    /**
     * Wrap options with custom callback for gathering statistics about request
     *
     * @param array $options options
     *
     * @return mixed
     */
    protected function wrapOptions(array $options)
    {
        if ($this->performanceLogger == null) {
            return $options;
        }
        if ($options == null) {
            $options = [];
        }
        $callback = $options['on_stats'] ?? null;
        // need to create here because we log time when request was created
        $logEntry = new LogEntry();
        $options['on_stats'] = function (\GuzzleHttp\TransferStats $stats) use ($callback, $logEntry) {
            if ($callback != null) {
                $callback($stats);
            }
            $logEntry->setUrl($stats->getEffectiveUri());
            $logEntry->setTotalTime($stats->getTransferTime());
            $logEntry->setMethod($stats->getRequest()->getMethod());
            if ($stats->hasResponse()) {
                $logEntry->setResponseHeaders($stats->getResponse()->getHeaders());
                $logEntry->setResponseLength($stats->getResponse()->getBody()->getSize());
                $logEntry->setStatusCode($stats->getResponse()->getStatusCode());
            } else {
                $error = $stats->getHandlerErrorData();
                if ($error instanceof \Exception) {
                    $logEntry->setError($error->getMessage());
                } elseif (is_string($error)) {
                    $logEntry->setError($error);
                } elseif (is_int($error)) {
                    $logEntry->setError((string)$error);
                }
            }
            $this->performanceLogger->writeEntryToLog($logEntry);
        };
        return $options;
    }
}
