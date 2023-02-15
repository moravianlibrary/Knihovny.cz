<?php
declare(strict_types=1);

/**
 * SOLR connector.
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
namespace KnihovnyCz\Search\Solr\Backend;

use KnihovnyCz\Search\Solr\DeduplicationHelper;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\PhpEnvironment\Request;
use VuFindSearch\Backend\Exception\HttpErrorException;
use VuFindSearch\ParamBag;

/**
 * SOLR connector.
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class Connector extends \VuFindSearch\Backend\Solr\Connector
{
    /**
     * Request
     *
     * @var Request
     */
    protected $request = null;

    /**
     * Performance logger
     *
     * @var PerformanceLogger
     */
    protected $performanceLogger = null;

    /**
     * Set request
     *
     * @param Request $request request
     *
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Set performance logger
     *
     * @param PerformanceLogger $logger performance logger
     *
     * @return void
     */
    public function setPerformanceLogger(PerformanceLogger $logger)
    {
        $this->performanceLogger = $logger;
    }

    /**
     * Execute a search.
     *
     * @param ParamBag $params Parameters
     *
     * @return string
     */
    public function search(ParamBag $params)
    {
        $switchToParentQuery = $params->contains('switchToParentQuery', true);
        if ($switchToParentQuery) {
            $params->remove('switchToParentQuery');
            $params = $this->switchToParentQuery($params);
        }
        return parent::search($params);
    }

    /**
     * Send request the SOLR and return the response.
     *
     * @param HttpClient $client Prepared HTTP client
     *
     * @return string Response body
     *
     * @throws RemoteErrorException  SOLR signaled a server error (HTTP 5xx)
     * @throws RequestErrorException SOLR signaled a client error (HTTP 4xx)
     */
    protected function send(HttpClient $client)
    {
        $this->debug(
            sprintf('=> %s %s', $client->getMethod(), $client->getUri())
        );
        $requestId = $this->request->getHeader("X-Request-ID");
        if ($requestId) {
            $client->setHeaders(['X-Request-ID' => $requestId->getFieldValue()]);
        }
        $time     = microtime(true);
        $response = $client->send();
        $time     = microtime(true) - $time;
        $this->debug(
            sprintf(
                '<= %s %s',
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ),
            ['time' => $time]
        );
        if ($this->performanceLogger != null) {
            $this->performanceLogger->log($client, $response, $time);
        }
        if (!$response->isSuccess()) {
            throw HttpErrorException::createFromResponse($response);
        }
        return $response->getBody();
    }

    /**
     * Switch to parent query
     *
     * @param \VuFindSearch\ParamBag $params Search parameters
     *
     * @return \VuFindSearch\ParamBag
     */
    protected function switchToParentQuery($params)
    {
        $qt = $params->get('qt');
        $edismax = $qt != null && $qt[0] == 'edismax';
        $query = $params->get('q');
        $parentFilter = DeduplicationHelper::PARENT_FILTER;
        $baseFilter = "{!child of='$parentFilter'} $parentFilter";
        $newQuery = $baseFilter . ' AND {!type=lucene v=$parentQuery}';
        if ($edismax) {
            $newQuery = $baseFilter
                . ' AND {!type=edismax qf=$qf bf=$bf bq=$bq v=$parentQuery}';
        }
        $params->set('q', $newQuery);
        $params->set('parentQuery', $query);
        $params->set('qt', 'standard');
        return $params;
    }
}
