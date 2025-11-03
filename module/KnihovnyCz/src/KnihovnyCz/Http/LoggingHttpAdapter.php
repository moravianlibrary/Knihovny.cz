<?php

namespace KnihovnyCz\Http;

use Laminas\Http\Client\Adapter\AdapterInterface;
use Laminas\Http\Response;

/**
 * Class LoggingHttpAdapter
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class LoggingHttpAdapter implements \Laminas\Http\Client\Adapter\AdapterInterface
{
    /**
     * Adapter to delegate to
     *
     * @var AdapterInterface
     */
    protected AdapterInterface $delegate;

    /**
     * Performance logger
     *
     * @var \KnihovnyCz\Http\PerformanceLogger
     */
    protected PerformanceLogger $performanceLogger;

    /**
     * Log entry
     *
     * @var LogEntry|null
     */
    protected ?LogEntry $logEntry;

    /**
     * Time when the request started - Unix timestamp with microseconds
     *
     * @var float
     */
    protected float $startTime;

    /**
     * Constructor
     *
     * @param AdapterInterface       $delegate   Adapter to delegate to
     * @param PerformanceLogger|null $perfLogger Performance Logger
     */
    public function __construct(AdapterInterface $delegate, ?PerformanceLogger $perfLogger = null)
    {
        $this->delegate = $delegate;
        $this->performanceLogger = $perfLogger;
        $this->logEntry = null;
    }

    /**
     * Set the configuration array for the adapter
     *
     * @param array $options options
     *
     * @return void
     */
    public function setOptions($options = [])
    {
        $this->delegate->setOptions($options);
    }

    /**
     * Connect to the remote server
     *
     * @param string $host   host
     * @param int    $port   port
     * @param bool   $secure secure
     *
     * @return void
     */
    public function connect($host, $port = 80, $secure = false)
    {
        $this->resetTimer();
        return $this->call(
            function () use ($host, $port, $secure) {
                $this->delegate->connect($host, $port, $secure);
            },
            'connect'
        );
    }

    /**
     * Send request to the remote server
     *
     * @param string           $method  method
     * @param \Laminas\Uri\Uri $url     url
     * @param string           $httpVer http version
     * @param array            $headers headers
     * @param string           $body    body
     *
     * @return string          Request as text
     */
    public function write(
        $method,
        $url,
        $httpVer = '1.1',
        $headers = [],
        $body = ''
    ) {
        $this->getEntryLog()->setMethod($method);
        $this->getEntryLog()->setUrl($url);
        return $this->call(
            function () use ($method, $url, $httpVer, $headers, $body) {
                return $this->delegate->write($method, $url, $httpVer, $headers, $body);
            },
            'write'
        );
    }

    /**
     * Read response from server
     *
     * @return string
     */
    public function read()
    {
        return $this->call(
            function () {
                $result = $this->delegate->read();
                $response = Response::fromString($result);
                $this->getEntryLog()->setResponseLength(strlen($response->getContent()));
                $this->getEntryLog()->setResponseHeaders($response->getHeaders()->toArray());
                $this->getEntryLog()->setStatusCode($response->getStatusCode());
                return $result;
            },
            'read'
        );
    }

    /**
     * Close the connection to the server
     *
     * @return void
     */
    public function close()
    {
        try {
            $this->delegate->close();
        } finally {
            $this->writeEntryToLog();
        }
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        $this->writeEntryToLog();
    }

    /**
     * Reset to initial state
     *
     * @return void
     */
    protected function resetTimer(): void
    {
        $this->startTime = microtime(true);
    }

    /**
     * Call function and measure its execution time
     *
     * @param callable $func   function to call
     * @param string   $method method name
     *
     * @return mixed|string|null
     * @throws \Exception
     */
    protected function call(callable $func, string $method)
    {
        $exception = null;
        $result = null;
        try {
            $result = $func();
        } catch (\Exception $e) {
            $exception = $e;
        }
        if ($exception != null) {
            $errMsg = $exception->getMessage();
            $this->getEntryLog()->setError("Exception in $method: " . $errMsg);
            $this->writeEntryToLog();
            throw $exception;
        }
        return $result;
    }

    /**
     * Return current entry log or create new
     *
     * @return \KnihovnyCz\Http\LogEntry
     */
    protected function getEntryLog()
    {
        if ($this->logEntry == null) {
            $this->logEntry = new LogEntry();
        }
        return $this->logEntry;
    }

    /**
     * Get the duration of the request
     *
     * @param float $start when the request started
     * @param float $end   when the request ended
     *
     * @return float   formatted duration time
     */
    protected function getDuration($start, $end = null): float
    {
        $end ??= microtime(true);
        return $end - $start;
    }

    /**
     * Write the log entry to the logger
     *
     * @return void
     */
    protected function writeEntryToLog(): void
    {
        if ($this->logEntry == null) {
            return;
        }
        $this->getEntryLog()->setTotalTime($this->getDuration($this->startTime));
        $this->performanceLogger->writeEntryToLog($this->getEntryLog());
        $this->resetTimer();
        $this->logEntry = null;
    }
}
