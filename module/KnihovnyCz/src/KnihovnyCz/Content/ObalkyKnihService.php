<?php

/**
 * Class ObalkyKnihService
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
 * @package  KnihovnyCz\Content
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\Content;

class ObalkyKnihService extends \VuFind\Content\ObalkyKnihService
{
    protected $authorityApiUrl;

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config $config Configuration for service
     */
    public function __construct(\Laminas\Config\Config $config)
    {
        parent::__construct($config);
        if (!isset($config->authority_endpoint)) {
            throw new \Exception(
                "Configuration for ObalkyKnih.cz service is not valid"
            );
        }
        $this->authorityApiUrl =
            $config->base_url[0] . $config->authority_endpoint . '/meta';
    }

    public function getAuthorityData(string $authId)
    {
        $cacheKey = $this->createCacheKey(['authority_id' => $authId]);
        $cachedData = $this->getCachedData($cacheKey);
        if ($cachedData === null) {
            $cachedData = $this->getAuthorityFromService($authId);
            $this->putCachedData($cacheKey, $cachedData);
        }
        return $cachedData;
    }

    protected function getAuthorityFromService(string $authId)
    {
        $url = $this->authorityApiUrl . "?";
        $url .= http_build_query(['auth_id' => $authId]);
        $client = $this->getHttpClient($url);
        try {
            $response = $client->send();
        } catch (\Exception $e) {
            return null;
        }
        return $response->isSuccess() ? json_decode($response->getBody())[0] : null;
    }

}
