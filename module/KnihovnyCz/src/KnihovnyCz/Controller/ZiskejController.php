<?php

declare(strict_types=1);
/**
 * Class ZiskejController
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

use Laminas\Http\Response;
use VuFind\Exception\LibraryCard;

/**
 * Class ZiskejController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejController extends AbstractBase
{
    /**
     * Ziskej payment page
     *
     * @return \Laminas\Http\Response
     */
    public function paymentAction(): Response
    {
        $eppnDomain = $this->params()->fromRoute('eppnDomain');
        $ticketId = $this->params()->fromRoute('ticketId');

        return $this->redirect()->toRoute(
            'myresearch-ziskej-mvs-ticket',
            [
                'eppnDomain' => $eppnDomain,
                'ticketId' => $ticketId,
            ]
        );
    }

    /**
     * Ziskej order finished page
     *
     * @return \Laminas\View\Model\ViewModel|mixed
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \VuFind\Exception\LibraryCard
     */
    public function finishedAction()
    {
        //@todo try/catch

        $eppnDomain = $this->params()->fromRoute('eppnDomain');
        $ticketId = $this->params()->fromRoute('ticketId');

        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }

        $userCard = $user->getCardByEppnDomain($eppnDomain);
        if (!$userCard || !$userCard->eppn) {
            throw new LibraryCard('Library Card Not Found');
        }

        /**
         * Ziskej API connector
         *
         * @var \Mzk\ZiskejApi\Api $ziskejApi
         */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $ticket = $ziskejApi->getTicket($userCard->eppn, $ticketId);

        return $this->createViewModel(
            [
                'userCard' => $userCard,
                'ticket' => $ticket,
            ]
        );
    }
}
