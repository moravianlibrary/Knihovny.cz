<?php
declare(strict_types=1);

/**
 * Class InvolvedLibrariesService
 *
 * PHP version 8
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\Content
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Content;

use Laminas\Stdlib\Parameters;
use VuFind\Search\Results\PluginManager as ResultsManager;

/**
 * Class InvolvedLibrariesService
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Content
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InvolvedLibrariesService
{
    /**
     * Results manager
     *
     * @var ResultsManager
     */
    protected ResultsManager $resultsManager;

    /**
     * Constructor
     *
     * @param ResultsManager $resultsManager Results manager
     */
    public function __construct(ResultsManager $resultsManager)
    {
        $this->resultsManager = $resultsManager;
    }

    /**
     * Get libraries involved in project Knihovny.cz
     *
     * @return array
     */
    public function getInvolvedLibraries(): array
    {
        $lookfor = "portal_facet_mv:\"KNIHOVNYCZ_YES\"";
        /**
         * Search results
         *
         * @var \VuFind\Search\Search2\Results $results
         */
        $results = $this->resultsManager->get('Search2');
        $params = $results->getParams();
        $params->getOptions()->disableHighlighting();
        $params->getOptions()->spellcheckEnabled(false);
        $params->getOptions()->setLimitOptions([1000]);
        $params->initFromRequest(new Parameters(['lookfor' => $lookfor ]));
        $libraries = [];
        foreach ($results->getResults() as $library) {
            $name = $library->getTranslatedNameBySource();
            $region = $library->getRegion();
            $sourceId = $library->getCpkCode();
            if ($name !== '' && !isset($libraries[$region][$sourceId])) {
                $libraries[$region][$sourceId] = [
                    'name' => $name,
                    'id' => $library->getUniqueID(),
                ];
            }
        }
        uksort($libraries, 'strcoll');
        return $libraries;
    }
}
