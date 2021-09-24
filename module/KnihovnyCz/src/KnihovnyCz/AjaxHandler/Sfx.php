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

use Laminas\Mvc\Controller\Plugin\Params;
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
class Sfx extends AbstractBase implements \VuFindHttp\HttpServiceAwareInterface,
    \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;

    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Configuration
     *
     * @var \Laminas\Config\Config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config $config Configuration
     */
    public function __construct(\Laminas\Config\Config $config)
    {
        $this->config = $config;
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
        $results = [];
        $query = $this->getSfxQuery($params);
        $servers = $this->config->Sfx->toArray();
        $default = $servers['default'];
        $free = $this->callSfx($default, $query);
        if ($free) {
            $results['default'] = [
                'label' => $this->translate('Fulltext'),
                'url'   => $free,
            ];
        } else {
            foreach ($servers as $code => $sfxUrl) {
                if ($code == 'default') {
                    continue;
                }
                $link = $this->callSfx($sfxUrl, $query);
                if ($link) {
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
        $query = [
            'sfx.response_type' => 'simplexml',
            'svc.fulltext' => 'yes',
        ];
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
     * @param string $sfxUrl SFX url
     * @param array  $query  query parameters
     *
     * @return false|string
     */
    protected function callSfx($sfxUrl, $query)
    {
        $client = $this->httpService->createClient($sfxUrl);
        $parameters = $query + $this->extractParameters($sfxUrl);
        $client->setParameterGet($parameters);
        $response = $client->send();
        $xml = simplexml_load_string($response->getBody());
        if (!$xml) {
            return false;
        }
        foreach ($xml->targets->target as $target) {
            if ($target->service_type == 'getFullTxt') {
                return (string)$target->target_url;
            }
        }
        return false;
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
