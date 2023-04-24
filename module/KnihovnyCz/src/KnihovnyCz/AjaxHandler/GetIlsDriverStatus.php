<?php

/**
 * Class Get ILS driver status
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2023.
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
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\ILS\Connection;
use VuFind\Session\Settings as SessionSettings;
use VuFindSearch\Command\RandomCommand;
use VuFindSearch\ParamBag;
use VuFindSearch\Query\Query;
use VuFindSearch\Service as SearchService;

/**
 * Class Get ILS driver status
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GetIlsDriverStatus extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * Number of tested records
     *
     * @var int
     */
    protected int $testedRecordsCount = 5;

    /**
     * Minimal number of successful tests to pass
     *
     * @var int
     */
    protected int $successThreshold = 4;

    /**
     * Search backend Id
     *
     * @var string
     */
    protected string $searchBackendId = 'Solr';

    /**
     * Session settings
     *
     * @var SessionSettings
     */
    protected $sessionSettings = null;

    /**
     * ILS connection
     *
     * @var Connection
     */
    protected Connection $ils;

    /**
     * Search service
     *
     * @var SearchService
     */
    protected SearchService $searchService;

    /**
     * Constructor
     *
     * @param SessionSettings $ss            Session settings
     * @param Connection      $ils           ILS connection
     * @param SearchService   $searchService Search service
     */
    public function __construct(
        SessionSettings $ss,
        Connection $ils,
        SearchService $searchService
    ) {
        $this->sessionSettings = $ss;
        $this->ils = $ils;
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
        $this->disableSessionWrites(); // avoid session write timing bug
        $source = $params->fromQuery('source', $params->fromPost('source', ''));
        $records = $this->getRandomRecords($source);
        $successNumber = 0;
        $testedRecords = array_map(
            function ($record) {
                return $record->getUniqueId();
            },
            $records
        );

        foreach ($records as $record) {
            try {
                $this->ils->getStatus($record->getUniqueId());
            } catch (\Exception $e) {
                continue;
            }
            $successNumber++;
        }
        $return = [
            'OK' => $successNumber >= $this->successThreshold,
            'score' => $successNumber,
            'testedRecords' => $testedRecords,
        ];
        return $this->formatResponse($return, 200);
    }

    /**
     * Get random records
     *
     * @param string $source Source name
     *
     * @return int
     */
    protected function getRandomRecords(string $source): array
    {
        $command = new RandomCommand(
            $this->searchBackendId,
            new Query(sprintf('id:%s.*', $source)),
            $this->testedRecordsCount,
            new ParamBag(['fl' => 'id'])
        );
        $results = $this->searchService->invoke($command)->getResult();
        return $results->getRecords();
    }
}
