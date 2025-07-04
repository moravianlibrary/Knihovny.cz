<?php

namespace KnihovnyCz\Http;

use Laminas\Http\PhpEnvironment\Request;
use VuFind\Log\LoggerAwareTrait;

/**
 * Class PerformanceLogger
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class PerformanceLogger
{
    use LoggerAwareTrait;

    /**
     * Headers to log from originating request
     *
     * @var array
     */
    private static $ORIG_REQUEST_HEADERS = [
        'X-Request-ID',
        'Referer',
    ];

    /**
     * Prefixes of headers to log from originating request - currently not used
     *
     * @var array
     */
    private static $ORIG_REQUEST_PREFIXED_HEADERS = [
    ];

    /**
     * Originating request
     *
     * @var array
     */
    protected array $origRequestEntry;

    /**
     * File to log, null if use standard logger
     *
     * @var string|null
     */
    protected ?string $file;

    /**
     * Constructor
     *
     * @param Request|null $request Request
     * @param string       $file    File to log to (null to use logging instead)
     */
    public function __construct(Request $request = null, $file = null)
    {
        $ipAddress = $request?->getHeader('X-Forwarded-For', null)?->getFieldValue()
            ?? $request?->getServer('REMOTE_ADDR');
        $url = $request?->getRequestUri();
        $this->origRequestEntry = [
            'originating_url' => $url,
            'ip' => $ipAddress,
        ];
        $origRequestHeaders = LogEntryHelper::filterHeaders(
            self::$ORIG_REQUEST_HEADERS,
            self::$ORIG_REQUEST_PREFIXED_HEADERS,
            $request?->getHeaders()?->toArray() ?? []
        );
        if (!empty($origRequestHeaders)) {
            $this->origRequestEntry['orig_req_headers'] = $origRequestHeaders;
        }
        $this->file = $file;
    }

    /**
     * Write the log entry to the logger
     *
     * @param LogEntry $logEntry log entry
     *
     * @return void
     */
    public function writeEntryToLog(LogEntry $logEntry)
    {
        $entry = $logEntry->toArray($this->file != null);
        $entry = array_merge($entry, $this->origRequestEntry);
        $json = json_encode($entry, JSON_UNESCAPED_SLASHES) . "\n";
        if ($this->file != null) {
            file_put_contents($this->file, $json, FILE_APPEND);
        } else {
            $this->debug($json);
        }
    }
}
