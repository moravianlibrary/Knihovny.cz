<?php
declare(strict_types=1);

/**
 * Class EmbeddedController
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

use KnihovnyCz\RecordDriver\SolrLibrary;
use Laminas\Http\Header\ContentSecurityPolicy;
use Laminas\View\Model\ViewModel;
use VuFindSearch\Command\SearchCommand;
use VuFindSearch\ParamBag;
use VuFindSearch\Query\Query;

/**
 * Class EmbeddedController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class EmbeddedController extends SearchController
{
    /**
     * Display directory of libraries
     *
     * @return \Laminas\View\Model\ViewModel
     * @throws \Exception
     */
    public function librariesAction(): ViewModel
    {
        $selectedRegion = $this->params()->fromRoute('region', null);
        $selectedDistrict = $this->params()->fromRoute('district', null);
        $color = $this->params()->fromQuery('color', '#e5004b');

        $this->disableSessionWrites();
        $headers = $this->getResponse()->getHeaders();
        $cspHeader = $headers->get('Content-Security-Policy');
        if ($cspHeader === false) {
            $cspHeader = new ContentSecurityPolicy();
            $headers->addHeader($cspHeader);
        }
        if (is_iterable($cspHeader)) {
            $cspHeader = $cspHeader->current();
        }
        $cspHeader->setDirective('frame-ancestors', ['*']);
        $view = $this->createViewModel();
        $view->setTemplate('embedded/libraries');
        $view->setTerminal(true);
        $lang = $this->params()->fromQuery('lang', null);
        if ($lang != null) {
            $this->setLanguage($lang);
        }

        $config = $this->getConfig("config");

        $queryString = '
(function_search_txt_mv:"pověřená regionální funkcí"
OR
regional_library_txt:*)
';

        if (!empty($selectedRegion)) {
            $queryString .= '
AND
region_search_txt:"' . $selectedRegion . '"
';
        }

        if (!empty($selectedDistrict)) {
            $queryString .= '
AND
district_exact:"' . $selectedDistrict . '"
';
        }

        $query = new Query($queryString);
        $paramBag = new ParamBag(
            [
                'fl' => implode(
                    ',',
                    [
                    'id',
                    'record_format',  // return SolrLibrary instead of SolrDefault
                    'local_ids_str_mv',
                    'function_search_txt_mv',
                    'regional_library_txt',
                    'region_search_txt',
                    'name_display',
                    'sigla_search_txt',
                    'district_search_txt',
                    'district_exact',
                    'town_str',
                    ]
                ),
            ]
        );
        $command = new SearchCommand('Solr', $query, 0, 99999, $paramBag);

        $searchService = $this->serviceLocator->get(\VuFindSearch\Service::class);

        $result = $searchService->invoke($command)->getResult();

        $records = $result->getRecords();

        $libraries = array_map(
            function (SolrLibrary $record) {
                return [
                    'id' => $record->getUniqueID(),
                    'sigla' => $record->getSiglaSearchTxt(),
                    'title' => $record->getTitle(),
                    'link' => $record->getChildrenIds()[0],
                    'parent' => $record->getRegionalLibraryTxt(),
                    'functions' => $record->getFunctionSearchTxtMv(),
                    'is_regional' => in_array(
                        'pověřená regionální funkcí',
                        $record->getFunctionSearchTxtMv()
                    ),
                    'region' => $record->getRegionSearchTxt(),
                    'district' => $record->getDistrictSearchTxt(),
                    'town' => $record->getTownStr(),
                ];
            },
            $records
        );
        usort(
            $libraries,
            function ($a, $b) {
                return strcmp($a['title'], $b['title']);
            }
        );
        usort(
            $libraries,
            function ($a, $b) {
                return strcmp($a['town'], $b['town']);
            }
        );

        $regions = array_unique(
            array_map(
                function (array $item) {
                    return $item['region'];
                },
                array_filter(
                    $libraries,
                    function (array $item) {
                        return !empty($item['region']);
                    }
                )
            )
        );
        sort($regions);

        $tree = [];
        foreach ($regions as $region) {
            $districts = [];
            foreach ($libraries as $lib) {
                if ($lib['region'] === $region) {
                    $districts[$lib['district']] = [];
                }
            }
            ksort($districts);
            $tree[$region] = $districts;
        }

        foreach ($libraries as $lib) {
            if (!empty($lib['region']) && !empty($lib['district'])) {
                $tree[$lib['region']][$lib['district']][$lib['sigla']] = $lib;
            }
        }

        $view->setVariables(
            [
                'selectedRegion' => $selectedRegion,
                'selectedDistrict' => $selectedDistrict,
                'tree' => $tree,
                'theme' => $config->Site->theme ?? '',
                'color' => $color
            ]
        );
        return $view;
    }
}
