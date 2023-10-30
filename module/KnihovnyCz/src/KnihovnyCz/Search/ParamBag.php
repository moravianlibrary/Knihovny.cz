<?php

namespace KnihovnyCz\Search;

/**
 * Lightweight wrapper for request parameters.
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class ParamBag extends \VuFindSearch\ParamBag
{
    /**
     * Is child filter enabled?
     *
     * @var bool
     */
    protected $applyChildFilter = true;

    /**
     * Set appply child filter
     *
     * @param bool $applyChildFilter apply child filter
     *
     * @return void
     */
    public function setApplyChildFilter(bool $applyChildFilter)
    {
        $this->applyChildFilter = $applyChildFilter;
    }

    /**
     * Is child filter enabled?
     *
     * @return bool
     */
    public function isApplyChildFilter()
    {
        return $this->applyChildFilter;
    }
}
