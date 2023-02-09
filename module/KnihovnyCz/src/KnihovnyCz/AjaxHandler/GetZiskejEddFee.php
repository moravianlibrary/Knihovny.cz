<?php
declare(strict_types=1);

/**
 * Class Get Holding
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
 * @package  KnihovnyCz\AjaxHandler
 * @author   Robert Sipek <robert.sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use Mzk\ZiskejApi\Api;
use Mzk\ZiskejApi\Enum\TicketEddSubtype;
use Mzk\ZiskejApi\Enum\ZiskejSettings;

/**
 * Class Get Ziskej Edd Fee
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Robert Sipek <robert.sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GetZiskejEddFee extends \VuFind\AjaxHandler\AbstractBase
    implements \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Ziskej API
     *
     * @var \Mzk\ZiskejApi\Api
     */
    protected Api $ziskejApi;

    /**
     * Constructor
     *
     * @param \Mzk\ZiskejApi\Api $ziskejApi Ziskej API
     */
    public function __construct(
        Api $ziskejApi
    ) {
        $this->ziskejApi = $ziskejApi;
    }

    /**
     * Handle request
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array
     * @throws \Http\Client\Exception
     */
    public function handleRequest(Params $params): array
    {
        $pagesFrom = (int)$params->fromQuery('pages_from');
        $pagesTo = (int)$params->fromQuery('pages_to');
        $eddSubtype = (string)$params->fromQuery('edd_subtype');

        if ($pagesTo < 1 || $pagesFrom < 1) {
            return $this->formatResponse(
                $this->translate(
                    'Ziskej::error_pages_limit_min',
                    ['%%count%%' => 1]
                ),
                self::STATUS_HTTP_ERROR
            );
        }

        $totalPages = ($pagesTo - $pagesFrom) + 1;

        if ($totalPages < 1) {
            return $this->formatResponse(
                $this->translate(
                    'Ziskej::error_pages_limit_min',
                    ['%%count%%' => 1]
                ),
                self::STATUS_HTTP_ERROR
            );
        }

        if ($eddSubtype === TicketEddSubtype::SELECTION) {
            if ($totalPages > ZiskejSettings::EDD_SELECTION_MAX_PAGES) {
                return $this->formatResponse(
                    $this->translate(
                        'Ziskej::error_max_total_pages_exceeded',
                        ['%%limit%%' => ZiskejSettings::EDD_SELECTION_MAX_PAGES]
                    ),
                    self::STATUS_HTTP_ERROR
                );
            }
        }

        try {
            $fee = $this->ziskejApi->getEddEstimateFee($totalPages, $eddSubtype);

            return $this->formatResponse(
                [
                    'fee' => $fee->getFee(),
                    'is_valid' => $fee->isValid(),
                    'total_pages' => $totalPages,
                    'message_subtotal' => $this->translate(
                        'Ziskej::message_subtotal_fee_info',
                        [
                            '%%price%%' => $fee->getFee(),
                            '%%pages%%' => $totalPages,
                        ]
                    ),
                    'message_total' => $this->translate(
                        'Ziskej::message_total_fee_info',
                        ['%%total%%' => $fee->getFee()]
                    ),
                ]
            );
        } catch (\Exception $e) {
            return $this->formatResponse($e->getMessage(), self::STATUS_HTTP_ERROR);
        }
    }
}