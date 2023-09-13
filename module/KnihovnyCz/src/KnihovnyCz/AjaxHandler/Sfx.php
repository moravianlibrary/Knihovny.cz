<?php

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

declare(strict_types=1);

namespace KnihovnyCz\AjaxHandler;

use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use Laminas\Mvc\Controller\Plugin\Params;
use Psr\Http\Message\ResponseInterface;
use VuFind\AjaxHandler\AbstractBase;

use function count;

/**
 * "Get Autocomplete Suggestions" AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Sfx extends AbstractBase implements
    \VuFind\I18n\Translator\TranslatorAwareInterface,
    \Laminas\Log\LoggerAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;
    use \VuFind\Log\LoggerAwareTrait;

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
     * Auth Manager
     *
     * @var \VuFind\Auth\Manager
     */
    protected $authManager;

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config                $config      Configuration
     * @param \KnihovnyCz\Service\GuzzleHttpService $httpService HTTP service
     * @param \VuFind\Auth\Manager                  $authManager Auth manager
     */
    public function __construct(
        \Laminas\Config\Config $config,
        \KnihovnyCz\Service\GuzzleHttpService $httpService,
        \VuFind\Auth\Manager $authManager
    ) {
        $this->config = $config;
        $this->httpService = $httpService;
        $this->authManager = $authManager;
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
            $link = ($directLink) ? $defaultLinks[0] :
                $this->getSfxUrl($default, $queryParams);
            $results['default'] = [
                'label' => $this->translate('sfx_fulltext_link'),
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
                $links = [];
                try {
                    $links = $this->parseResponse($promise->wait());
                } catch (\Exception $ex) {
                    $url = $this->getSfxUrl($servers[$code], $apiQueryParams);
                    $this->logWarning(
                        'Exception thrown when calling SFX',
                        [$url, $ex]
                    );
                }
                if (!empty($links)) {
                    $directLink = ($directLinking && count($links) == 1);
                    $link = ($directLink) ? $links[0] :
                        $this->getSfxUrl($servers[$code], $queryParams);
                    $results[$code] = [
                        'label' => $this->translate(['Source', $code]),
                        'url'   => $link,
                    ];
                }
            }
        }
        /**
         * User model
         *
         * @var \KnihovnyCz\Db\Row\User|false $user
         */
        $user = $this->authManager->isLoggedIn();
        if ($user) {
            $prefixes = $user->getLibraryPrefixes();
            uksort(
                $results,
                function ($a, $b) use ($prefixes) {
                    $a = array_search($a, $prefixes);
                    $a = ($a !== false) ? $a : PHP_INT_MAX;
                    $b = array_search($b, $prefixes);
                    $b = ($b !== false) ? $b : PHP_INT_MAX;
                    return (int)$a - (int)$b;
                }
            );
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
            if (
                ($key != 'rft_val_fmt'
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
        $url = $this->getSfxUrl($sfxUrl, $query);
        $this->logWarning('Calling SFX: ' . $url);
        $request = new Request('GET', $url);
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
