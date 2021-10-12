<?php
declare(strict_types=1);
/**
 * SFX AJAX handler
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2018.
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
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace KnihovnyCz\AjaxHandler;

use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Query;
use Laminas\Mvc\Controller\Plugin\Params;
use Psr\Http\Message\ResponseInterface;
use VuFind\AjaxHandler\AbstractBase;

/**
 * "Get Autocomplete Suggestions" AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Sfx extends AbstractBase
    implements \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Configuration
     *
     * @var \Laminas\Config\Config
     */
    protected $config;

    /**
     * Configuration
     *
     * @var \KnihovnyCz\Service\GuzzleHttpService
     */
    protected $httpService;

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config $config Configuration
     * @param \KnihovnyCz\Service\GuzzleHttpService $httpService HTTP service
     */
    public function __construct(\Laminas\Config\Config $config,
        \KnihovnyCz\Service\GuzzleHttpService $httpService
    ) {
        $this->config = $config;
        $this->httpService = $httpService;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $directLinking = $this->config->Sfx->direct_linking ?? true;
        $queryParams = $this->getSfxQuery($params);
        $apiQueryParams = $queryParams + [
            'sfx.response_type' => 'simplexml',
            'svc.fulltext' => 'yes',
        ];
        $servers = $this->config->SfxServers->toArray();
        $results = [];
        $defaultLinks = [];
        $default = $servers['default'] ?? null;
        if ($default != null) {
            $promise = $this->callSfx($default, $apiQueryParams);
            $response = $promise->wait();
            $defaultLinks = $this->parseResponse($response);
        }
        if (!empty($defaultLinks)) {
            $directLink = ($directLinking && count($defaultLinks) == 1);
            $link = ($directLink)? $defaultLinks[0] :
                $this->getSfxUrl($default, $queryParams);
            $results['default'] = [
                'label' => $this->translate('Fulltext'),
                'url'   => $link,
            ];
        } else {
            $promises = [];
            foreach ($servers as $code => $sfxUrl) {
                if ($code == 'default') {
                    continue;
                }
                $promises[$code] = $this->callSfx($sfxUrl, $apiQueryParams);
            }
            Utils::all($promises);
            foreach ($promises as $code => $promise) {
                $links = $this->parseResponse($promise->wait());
                if (!empty($links)) {
                    $directLink = ($directLinking && count($links) == 1);
                    $link = ($directLink)? $links[0] :
                        $this->getSfxUrl($servers[$code], $queryParams);
                    $results[$code] = [
                        'label' => $this->translate(['Source', $code]),
                        'url'   => $link,
                    ];
                }
            }
        }
        return $this->formatResponse($results);
    }

    /**
     * Extract SFX query from parameters.
     *
     * @param Params $params parameters
     *
     * @return array
     */
    protected function getSfxQuery(Params $params)
    {
        $query = [];
        foreach ($params->fromQuery() as $key => $value) {
            if ($key == 'method' || $key == 'sfx_institute') {
                continue;
            }
            // PHP is replacing . by _ in parameter name
            if (($key != 'rft_val_fmt'
                && substr($key, 0, 4) == 'rft_')
            ) {
                $key = str_replace('_', '.', $key);
            }
            $query[$key] = $value;
        }
        return $query;
    }

    /**
     * Call SFX server and return links to fulltext.
     *
     * @param string $sfxUrl SFX base URL
     * @param array  $query  query parameters
     *
     * @return \Http\Promise\Promise promise
     */
    protected function callSfx($sfxUrl, $query)
    {
        $client = $this->httpService->createClient();
        $request = new Request('GET', $this->getSfxUrl($sfxUrl, $query));
        return $client->sendAsyncRequest($request);
    }

    /**
     * Call SFX server and return links to fulltext.
     *
     * @param string $sfxUrl SFX base URL
     * @param array  $query  query parameters
     *
     * @return string URL
     */
    protected function getSfxUrl($sfxUrl, $query)
    {
        $queryPart = (string)parse_url($sfxUrl, PHP_URL_QUERY) ?? '';
        $sfxParams = Query::parse($queryPart);
        $params = http_build_query($sfxParams + $query);
        return strtok($sfxUrl, '?') . '?' . $params;
    }

    /**
     * Parse SFX response and return link to fulltext or false.
     *
     * @param ResponseInterface $response SFX response
     *
     * @return array fulltext links
     */
    protected function parseResponse($response)
    {
        $body = $response->getBody();
        $xml = simplexml_load_string($response->getBody()->getContents());
        if (!$xml) {
            return [];
        }
        $results = [];
        foreach ($xml->targets->target as $target) {
            if ($target->service_type == 'getFullTxt') {
                $results[] = (string)$target->target_url;
            }
        }
        return $results;
    }

    /**
     * Extract query parameters from URL
     *
     * @param string $url url
     *
     * @return array
     */
    protected function extractParameters($url)
    {
        $params = [];
        $queryPart = parse_url($url, PHP_URL_QUERY);
        if ($queryPart != null) {
            parse_str($queryPart, $params);
        }
        return $params;
    }
}
