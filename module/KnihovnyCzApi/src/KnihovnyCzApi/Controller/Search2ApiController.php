<?php

declare(strict_types=1);

namespace KnihovnyCzApi\Controller;

/**
 * Class Search2ApiController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCzApi\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Search2ApiController extends \VuFindApi\Controller\Search2ApiController
{
    /**
     * Record route uri
     *
     * @var string
     */
    protected $recordRoute = 'libraries/record';

    /**
     * Search route uri
     *
     * @var string
     */
    protected $searchRoute = 'libraries/search';

    /**
     * Descriptive label for the index managed by this controller
     *
     * @var string
     */
    protected $indexLabel = 'libraries';

    /**
     * Prefix for use in model names used by API
     *
     * @var string
     */
    protected $modelPrefix = 'Libraries';
}
