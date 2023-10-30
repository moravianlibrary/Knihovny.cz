<?php

declare(strict_types=1);

namespace KnihovnyCz\Related;

use KnihovnyCz\RecordDriver\SolrDefault;

/**
 * Class Links
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Related
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Links implements \VuFind\Related\RelatedInterface
{
    /**
     * Links
     *
     * @var array
     */
    protected array $links;

    /**
     * Module label
     *
     * @var string
     */
    protected string $label;

    /**
     * Establishes base settings for making recommendations.
     *
     * @param string      $settings Settings from config.ini
     * @param SolrDefault $driver   Record driver object
     *
     * @return void
     */
    public function init($settings, $driver)
    {
        $this->links = match ($settings) {
            'external' => $driver->tryMethod('getExternalLinks') ?? [],
            'identifiers' => $driver->tryMethod('getIdentifiersLinks') ?? [],
            'socialsites' => $driver->tryMethod('getSocialSitesLinks') ?? [],
            default => [],
        };
        $this->label = 'related_' . $settings . '_links';
    }

    /**
     * Get an array of external links
     *
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Get label for related module
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }
}
