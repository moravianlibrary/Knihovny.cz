<?php

namespace KnihovnyCz\Http;

use Laminas\Http\Response;

/**
 * Class LogEntry
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class LogEntry
{
    /**
     * Headers to log from response
     *
     * @var array
     */
    private static array $RESPONSE_HEADERS = [
        'X-Cache',
        'X-Generated-In',
    ];

    /**
     * Prefixes of headers to log from response
     *
     * @var array
     */
    private static array $RESPONSE_PREFIXED_HEADERS = [
        'X-Varnish',
    ];

    /**
     * Time when request was started
     *
     * @var \DateTime
     */
    private \DateTime $time;

    private ?string $method;

    private ?string $url;

    private ?int $statusCode = null;

    private ?array $responseHeaders = [];

    private ?int $responseLength = null;

    private ?float $totalTime;

    private ?string $error = null;

    /**
     * Create new log entry
     */
    public function __construct()
    {
        $this->time = new \DateTime();
    }

    /**
     * Get HTTP method
     *
     * @return void
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set HTTP method
     *
     * @param string $method METHOD
     *
     * @return void
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * Get ULR
     *
     * @return string URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set ULR
     *
     * @param string $url url
     *
     * @return void
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get HTTP status code
     *
     * @return string|null HTTP status code
     */
    public function getStatusCode(): ?string
    {
        return $this->statusCode;
    }

    /**
     * Set HTTP status code
     *
     * @param string $statusCode status code
     *
     * @return void
     */
    public function setStatusCode(?int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Get response headers
     *
     * @return array|null response headers
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * Set response headers
     *
     * @param array|null $responseHeaders response headers
     *
     * @return void
     */
    public function setResponseHeaders(array $responseHeaders): void
    {
        $this->responseHeaders = $responseHeaders;
    }

    /**
     * Get response lenght in bytes
     *
     * @return int|null response length
     */
    public function getResponseLength(): int
    {
        return $this->responseLength;
    }

    /**
     * Set response lenght in bytes
     *
     * @param int $responseLength response length
     *
     * @return void
     */
    public function setResponseLength(int $responseLength): void
    {
        $this->responseLength = $responseLength;
    }

    /**
     * Get total time of request
     *
     * @return float total time
     */
    public function getTotalTime(): float
    {
        return $this->totalTime;
    }

    /**
     * Set total time of request
     *
     * @param float $totalTime total time
     *
     * @return void
     */
    public function setTotalTime(float $totalTime): void
    {
        $this->totalTime = $totalTime;
    }

    /**
     * Get error
     *
     * @return string error
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Set error
     *
     * @param string $error error
     *
     * @return void
     */
    public function setError(string $error): void
    {
        $this->error = $error;
    }

    /**
     * Return entry as array
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'time' => $this->time->format('c'),
            'method' => $this->method,
            'url' => $this->url,
            'totalTime' => number_format($this->totalTime, 3),
        ];
        $sessionId = session_id();
        if (!empty($sessionId)) {
            $result['session_id'] = $sessionId;
        }
        if ($this->statusCode != null) {
            $result['statusCode'] = $this->statusCode;
        }
        if ($this->responseLength != null) {
            $result['responseLength'] = $this->responseLength;
        }
        if ($this->error != null) {
            $result['error'] = $this->error;
        }
        $responseHeaders = LogEntryHelper::filterHeaders(
            self::$RESPONSE_HEADERS,
            self::$RESPONSE_PREFIXED_HEADERS,
            $this->responseHeaders,
        );
        if (!empty($responseHeaders)) {
            $result['response_headers'] = $responseHeaders;
        }
        return $result;
    }
}
