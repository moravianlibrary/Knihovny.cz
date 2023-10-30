<?php

namespace KnihovnyCz\Search\Factory;

use KnihovnyCz\Search\UrlQueryHelper;

/**
 * Factory to build UrlQueryHelper.
 *
 * @category VuFind
 * @package  Search
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class UrlQueryHelperFactory extends \VuFind\Search\Factory\UrlQueryHelperFactory
{
    /**
     * UrlQueryHelperFactory constructor.
     */
    public function __construct()
    {
        $this->helperClass = UrlQueryHelper::class;
    }
}
