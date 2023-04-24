<?php

/**
 * Class GeoCoords
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2022.
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
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

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
