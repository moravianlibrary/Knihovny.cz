<?php
declare(strict_types=1);

/**
 * Class WayfFilterGenerator
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Service;

/**
 * Class WayfFilterGenerator
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class WayfFilterGenerator
{
    /**
     * Enabled identity providers
     *
     * @var \Laminas\Config\Config
     */
    protected \Laminas\Config\Config $shibbolethConfig;

    /**
     * Template for creating filter data
     *
     * @const array
     */
    const FILTER_PROTOTYPE = [
        'ver' => '2',
        'allowFeeds' => [
            'eduID.cz' => [
                'allowIdPs' => [],
            ],
            'SocialIdPs' => [
                'allowIdPs' => [],
            ],
        ],
    ];

    protected string $defaultFeed = 'eduID.cz';

    /**
     * WayfFilterGenerator constructor.
     *
     * @param \Laminas\Config\Config $config Shibboleth config - list of enabled
     * Identity providers
     */
    public function __construct(\Laminas\Config\Config $config)
    {
        $this->shibbolethConfig = $config;
    }

    /**
     * Generate filter
     *
     * @return string Base 64 encoded json
     */
    public function generate()
    {
        $filter = self::FILTER_PROTOTYPE;
        foreach ($this->shibbolethConfig as $idp) {
            $feed = $idp->feed ?? $this->defaultFeed;
            $filter['allowFeeds'][$feed]['allowIdPs'][] = $idp->entityId;
        }
        $jsonFilter = json_encode($filter, JSON_UNESCAPED_SLASHES);
        return base64_encode($jsonFilter ? $jsonFilter : '');
    }
}
