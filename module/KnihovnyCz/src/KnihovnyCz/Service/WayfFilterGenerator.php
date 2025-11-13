<?php

declare(strict_types=1);

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
     * @var \Vufind\Config\Config
     */
    protected \Vufind\Config\Config $shibbolethConfig;

    /**
     * Template for creating filter data
     *
     * @const array
     */
    public const FILTER_PROTOTYPE = [
        'ver' => '2',
        'allowFeeds' => [
            'eduID.cz' => [
                'allowIdPs' => [],
            ],
            'SocialIdPs' => [
                'allowIdPs' => [],
            ],
            'StandaloneIdP' => [
                'allowIdPs' => [],
            ],
        ],
    ];

    protected string $defaultFeed = 'eduID.cz';

    /**
     * WayfFilterGenerator constructor.
     *
     * @param \Vufind\Config\Config $config Shibboleth config - list of enabled
     * Identity providers
     */
    public function __construct(\Vufind\Config\Config $config)
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
            if (isset($idp['entityId'])) {
                $feed = $idp['feed'] ?? $this->defaultFeed;
                $filter['allowFeeds'][$feed]['allowIdPs'][] = $idp['entityId'];
            }
        }
        $jsonFilter = json_encode($filter, JSON_UNESCAPED_SLASHES);
        return base64_encode($jsonFilter ?: '');
    }
}
