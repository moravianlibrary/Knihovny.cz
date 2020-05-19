<?php

/**
 * Class LinkServiceAbstractBase
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
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\Service;

use VuFindHttp\HttpServiceAwareInterface;
use VuFindHttp\HttpServiceAwareTrait;

abstract class LinkServiceAbstractBase implements LinkServiceInterface, HttpServiceAwareInterface
{
    use HttpServiceAwareTrait;

    /**
     * Get data from service as array
     *
     * @param string $url    Base url
     * @param array  $params Parameters
     *
     * @return array
     */
    protected function getDataFromService(string $url, array $params = []): array
    {
        if (!empty($params)) {
            $url = $url . '?' . http_build_query($params);
        }
        $client = $this->httpService->createClient($url);
        $response = $client->send();
        if ($response->getStatusCode() !== 200) {
            return [];
        }
        return json_decode($response->getBody(), true);
    }
}