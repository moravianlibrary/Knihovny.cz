<?php

/**
 * "Get Item Statuses" AJAX handler
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
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace KnihovnyCz\AjaxHandler;

use KnihovnyCz\Db\Table\RecordStatus;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;
use VuFind\AjaxHandler\AbstractBase;

/**
 * "Get Item Statuses" AJAX handler
 *
 * This is responsible for printing the holdings information for a
 * collection of records in JSON format.
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GetItemStatuses extends AbstractBase
{
    /**
     * View renderer
     *
     * @var RendererInterface
     */
    protected RendererInterface $renderer;

    /**
     * User resource table
     *
     * @var RecordStatus
     */
    protected RecordStatus $recordStatus;

    /**
     * Constructor
     *
     * @param RendererInterface $renderer     Renderer
     * @param RecordStatus      $recordStatus User lists table
     */
    public function __construct(RendererInterface $renderer, RecordStatus $recordStatus)
    {
        $this->renderer = $renderer;
        $this->recordStatus = $recordStatus;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params): array
    {
        $recordIds = $params->fromQuery('id', []) + $params->fromPost('id', []);
        $statuses = $this->recordStatus->getByRecordIds($recordIds);
        $results = [];
        $foundIds = [];
        foreach ($statuses as $status) {
            $status = $status->asArray();
            $status['availability_message'] = $this->renderer->render(
                'ajax/status-available.phtml',
                ['status' => $status]
            );
            // Add empty callnumber because check_item_statuses.js requires them
            $status['callnumber'] = '';
            $results[] = $status;
            $foundIds[] = $status['id'];
        }
        $missingIds = array_diff($recordIds, $foundIds);
        if (!empty($missingIds)) {
            $unknownStatus = $this->renderer->render(
                'ajax/status-unknown.phtml',
            );
            foreach ($missingIds as $missingId) {
                $results[] = [
                    'id' => $missingId,
                    'availability_message' => $unknownStatus,
                    'callnumber' => '',
                ];
            }
        }
        return $this->formatResponse(['statuses' => $results]);
    }
}
