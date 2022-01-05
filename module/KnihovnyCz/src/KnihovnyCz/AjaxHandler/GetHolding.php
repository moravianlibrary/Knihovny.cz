<?php
/**
 * Class Get Holding
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

use KnihovnyCz\ILS\Logic\Holdings as HoldingsLogic;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\ILS\Connection;
use VuFind\ILS\Logic\Holds as Holds;
use VuFind\Session\Settings as SessionSettings;
use VuFind\View\Helper\Root\RecordLink as RecordLink;

/**
 * Class Get Holding
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GetHolding extends \VuFind\AjaxHandler\AbstractBase
    implements \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * ILS connection
     *
     * @var Connection
     */
    protected $ils;

    /**
     * Holds logic
     *
     * @var Holds
     */
    protected $holds;

    /**
     * Record link helper
     *
     * @var RecordLink
     */
    protected $recordLink;

    /**
     * Holdings logic helper
     *
     * @var HoldingsLogic
     */
    protected HoldingsLogic $holdingsLogic;

    /**
     * Constructor
     *
     * @param SessionSettings $ss            Session settings
     * @param Holds           $holds         Hold logic
     * @param RecordLink      $recordLink    Record link
     * @param HoldingsLogic   $holdingsLogic Holdings logic
     */
    public function __construct(
        SessionSettings $ss,
        Holds $holds,
        RecordLink $recordLink,
        HoldingsLogic $holdingsLogic
    ) {
        $this->sessionSettings = $ss;
        $this->holds = $holds;
        $this->recordLink = $recordLink;
        $this->holdingsLogic = $holdingsLogic;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     * @throws \Exception
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites(); // avoid session write timing bug
        $id = $params->fromPost('id', $params->fromQuery('id', null));
        $source = explode(".", $id)[0];
        $holding = $this->holds->getHoldings($id);
        $copy = [];
        $labels = [
            HoldingsLogic::STATUS_NOT_AVAILABLE => 'danger',
            HoldingsLogic::STATUS_AVAILABLE => 'success',
            HoldingsLogic::STATUS_TEMPORARY_NOT_AVAILABLE => 'warning',
            HoldingsLogic::STATUS_UNKNOWN => 'default',
            HoldingsLogic::STATUS_UNDECIDABLE => '',
        ];
        // ungroup holdings and set link
        foreach ($holding['holdings'] as $location => $hold) {
            foreach ($hold['items'] as $item) {
                if (isset($item['link'])) {
                    $item['link'] = $this->recordLink->getRequestUrl($item['link']);
                }
                if (isset($item['status'])) {
                    $holdingStatus = $this->holdingsLogic->getAvailabilityByStatus(
                        $item['status']
                    );
                    $item['label'] = $labels[$holdingStatus] ?? 'default';
                    $status = $this->translateWithSource(
                        $source,
                        $item['status'],
                        'HoldingStatus'
                    );
                    if ($status == $item['status']) {
                        $status = $this->translateString(
                            $status,
                            [],
                            $status,
                            'HoldingStatus'
                        );
                    }
                    $item['status'] = $status;
                }
                array_push($copy, $item);
            }
        }
        $response = [
            'status' => 'OK',
            'holding' => $copy
        ];
        return $this->formatResponse($response, 200);
    }

    /**
     * Translate with ILS source and domain
     *
     * @param string $source ILS driver source identifier
     * @param string $text   Text to translation
     * @param string $domain Translation domain
     *
     * @return string
     */
    protected function translateWithSource(
        string $source,
        string $text,
        string $domain
    ): string {
        return $this->translateString(
            $source . '_' . $text,
            [],
            $text,
            $domain
        );
    }
}
