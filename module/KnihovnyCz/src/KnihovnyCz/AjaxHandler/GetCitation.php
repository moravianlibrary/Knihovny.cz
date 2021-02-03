<?php
declare(strict_types=1);

/**
 * Class GetCitation
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
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\AjaxHandler;

use KnihovnyCz\Service\CitaceProService;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Session\Settings as SessionSettings;

/**
 * Class GetCitation
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GetCitation extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * CitacePro service
     */
    protected CitaceProService $citacePro;

    /**
     * Get citation ajax handler constructor.
     *
     * @param SessionSettings  $ss        Session settings
     * @param CitaceProService $citacePro CitacePro API service
     */
    public function __construct(SessionSettings $ss, CitaceProService $citacePro)
    {
        $this->sessionSettings = $ss;
        $this->citacePro = $citacePro;
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
        $this->disableSessionWrites();  // avoid session write timing bug
        $recordId = $params->fromPost('recordId');
        $citeStyle = $params->fromPost('citationStyle');

        try {
            $citation = $this->citacePro->getCitation($recordId, $citeStyle);
            if (!empty($citation)) {
                return $this->formatResponse($citation);
            }
        } catch (\Exception $e) {
            return $this->formatResponse($e->getMessage(), self::STATUS_HTTP_ERROR);
        }

        return $this->formatResponse('Unknown error', self::STATUS_HTTP_ERROR);
    }
}
