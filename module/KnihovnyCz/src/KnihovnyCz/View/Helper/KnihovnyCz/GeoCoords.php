<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use VuFind\Search\Base\Options;
use VuFind\Search\Base\Results;

/**
 * Class GeoCoords
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GeoCoords extends \VuFind\View\Helper\Root\GeoCoords
{
    /**
     * Get search URL if geo search is enabled for the specified search class ID,
     * false if disabled.
     *
     * @param Options $options Search options
     * @param Results $results Results
     *
     * @return string|bool
     */
    public function getSearchUrl(Options $options = null, Results $results = null)
    {
        // If the relevant module is disabled, bail out now:
        if (!$this->recommendationEnabled($options->getRecommendationSettings())) {
            return false;
        }
        $queryParams = ($results) ? $results->getUrlQuery()->getParamArray() : [];
        $queryParams['geographicSearch'] = true;
        $urlHelper = $this->getView()->plugin('url');
        return $urlHelper('search-results', [], ['query' => $queryParams]);
    }
}
