<?php

declare(strict_types=1);

namespace KnihovnyCz\Related;

use KnihovnyCz\RecordDriver\SolrDefault;

/**
 * Class Authorities
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Related
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Authorities implements \VuFind\Related\RelatedInterface
{
    /**
     * Related authorities
     *
     * @var array
     */
    protected array $relatedAuthorities;

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
        $this->relatedAuthorities = $driver->tryMethod('getRelatedAuthorities') ?? [];
    }

    /**
     * Get related authorities
     *
     * @return array
     */
    public function getRelatedAuthorities()
    {
        return $this->relatedAuthorities;
    }
}
