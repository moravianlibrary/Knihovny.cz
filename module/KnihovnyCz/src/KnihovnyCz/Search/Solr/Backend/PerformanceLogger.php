<?php

/**
 * Solr performance logger
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2022.
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
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */

declare(strict_types=1);

namespace KnihovnyCz\Search\Solr\Backend;

use Laminas\Http\Client as HttpClient;
use Laminas\Http\PhpEnvironment\Request;

use function floatval;

/**
 * Solr performance logger
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class PerformanceLogger
{
    /**
     * Request
     *
     * @var string
     */
    protected $file;

    /**
     * Request
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Request
     *
     * @var Request
     */
    protected $request;

    /**
     * Constructor
     *
     * @param string  $file    file
     * @param string  $baseUrl base URL
     * @param Request $request request
     */
    public function __construct($file, $baseUrl, $request)
    {
        $this->file = $file;
        $this->baseUrl = $baseUrl;
        $this->request = $request;
    }

    /**
     * Log response
     *
     * @param HttpClient             $client   client
     * @param \Laminas\Http\Response $response ressponse
     * @param float                  $time     time
     *
     * @return void
     */
    public function log($client, $response, $time)
    {
        $solrUrl = $client->getUri();
        $referer = $this->getHeader('Referer');
        $requestId = $this->getHeader('X-Request-ID');
        $ip = $this->getHeader('X-Forwarded-For');
        $response = $client->getResponse();
        $cache = $this->getHeaderFromResponse($response, 'X-Cache');
        $genTime = $this->getHeaderFromResponse($response, 'X-Generated-In');
        $solrTime = null;
        if (is_numeric($genTime)) {
            $solrTime = number_format(floatval($genTime), 3);
        }
        $perfEntry = [
            'time'         => date('c'),
            'ip'           => $ip,
            'session'      => session_id(),
            'x_request_id' => $requestId,
            'vufind_url'   => $this->getUrl(),
            'referer'      => $referer,
            'status'       => $response->getStatusCode(),
            'solr_url'     => (string)$solrUrl,
            'query_time'   => number_format($time, 3),
            'solr_time'    => $solrTime,
            'cache'        => $cache,
        ];
        $json = json_encode($perfEntry, JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents($this->file, $json, FILE_APPEND);
    }

    /**
     * Get header value
     *
     * @param string $header header name
     *
     * @return string
     */
    protected function getHeader($header)
    {
        if ($this->request == null) {
            return null;
        }
        $val = $this->request->getHeader($header);
        return $val ? $val->getFieldValue() : null;
    }

    /**
     * Get header value
     *
     * @param \Laminas\Http\Response $response response
     * @param string                 $header   header name
     *
     * @return string
     */
    protected function getHeaderFromResponse($response, $header)
    {
        $val = $response->getHeaders()->get($header);
        return $val ? $val->getFieldValue() : null;
    }

    /**
     * Get URL
     *
     * @return null|string
     */
    protected function getUrl()
    {
        if ($this->request == null) {
            return null;
        }
        $url = $this->request->getRequestUri();
        return rtrim($this->baseUrl, '/') . $url;
    }
}
