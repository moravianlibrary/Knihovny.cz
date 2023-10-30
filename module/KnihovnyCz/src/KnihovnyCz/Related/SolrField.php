<?php

namespace KnihovnyCz\Related;

use KnihovnyCz\RecordDriver\SolrDefault;

/**
 * Class SolrField
 *
 * @category VuFind
 * @package  KnihovnyCz\Related
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SolrField implements \VuFind\Related\RelatedInterface
{
    /**
     * Similar records
     *
     * @var array
     */
    protected $results;

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
        $this->results = $driver->getSimilarFromSolrField();
    }

    /**
     * Get an array of StdObjects representing items similar to the one
     * passed to the constructor.
     *
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
