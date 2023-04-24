<?php

/**
 * Knihovny.cz HTTP service class file.
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
 * @package  Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */

namespace KnihovnyCz\Service;

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
     * Constructor.
     *
     * @param array $proxyConfig Proxy configuration
     * @param array $defaults    Default HTTP options
     * @param array $config      Other configuration
     *
     * @return void
     */
    public function __construct(
        array $proxyConfig = [],
        array $defaults = [],
        array $config = []
    ) {
        parent::__construct($proxyConfig, $defaults, $config);
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
