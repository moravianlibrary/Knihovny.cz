<?php

/**
 * Class GuzzleHttpService
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
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
 * @package  KnihovnyCz\Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\Service;

use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Http\Adapter\Guzzle6\Client;

/**
 * Class GuzzleHttpService
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GuzzleHttpService
{
    /**
     * Configuration
     *
     * @param ?string $proxy proxy server to use
     */
    protected ?string $proxy;

    /**
     * GuzzleHttpService constructor.
     *
     * @param string $proxy proxy server to use
     */
    public function __construct($proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * Return a new HTTP client.
     *
     * @param array $config Configuration
     *
     * @return Client
     */
    public function createClient($config = [])
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        if ($this->proxy != null) {
            $stack->push(self::addProxy($this->proxy));
        }
        $config['handler'] = $stack;
        return Client::createWithConfig($config);
    }

    /**
     * Configure proxy
     *
     * @param string $proxy proxy server to use
     *
     * @return \Closure
     */
    public static function addProxy($proxy)
    {
        return function (callable $handler) use ($proxy) {
            return function ($request, array $options) use ($handler, $proxy) {
                $options['proxy'] = $proxy;
                return $handler($request, $options);
            };
        };
    }
}
