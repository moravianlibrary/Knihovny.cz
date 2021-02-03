<?php

/**
 * Class Edd - API for electronic document delivery
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
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
 * @package  KnihovnyCz\AjaxHandler
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Session\Settings as SessionSettings;
use VuFindSearch\Service as SearchService;

/**
 * Class Edd - API for electronic document delivery
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Edd extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * Search results plugin manager
     *
     * @var SearchService
     */
    protected $searchService = null;

    /**
     * Edd constructor.
     *
     * @param SessionSettings $ss            Session settings
     * @param SearchService   $searchService Search service class
     */
    public function __construct(SessionSettings $ss, SearchService $searchService)
    {
        $this->sessionSettings = $ss;
        $this->searchService = $searchService;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();  // avoid session write timing bug
        $query = new \VuFindSearch\Query\Query('*:*');
        $fq = [
            'merged_child_boolean:true',
        ];
        $childFilter = '{!child of=\'merged_boolean:true\'} merged_boolean:true';
        foreach (['issn', 'title'] as $parameter) {
            $value = $params->fromQuery($parameter, '');
            if (!empty($value)) {
                $fq[] = "$childFilter AND $parameter:" . addcslashes($value, '"');
            }
        }
        $year = $params->fromQuery('year', '');
        if (!empty($year)) {
            $fq[] = 'periodical_availability_int_mv:' . addcslashes($year, '"');
        }
        $params = new \VuFindSearch\ParamBag(
            ['fq' => $fq, 'fl' => 'id, sigla_display, record_format', 'hl' => false ]
        );
        $result = $this->searchService->search('Solr', $query, 0, 10, $params);
        $records = $result->getRecords();
        $results = [];
        foreach ($records as $record) {
            $results[] = [
                'id' => $record->getUniqueID(),
                'sigla' => $record->getSiglaDisplay(),
            ];
        }
        return $this->formatResponse($results, 200);
    }
}
