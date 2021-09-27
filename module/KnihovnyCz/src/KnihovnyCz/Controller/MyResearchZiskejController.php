<?php

declare(strict_types=1);

/**
 * Class MyResearchZiskejController
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2019.
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
 * @package  KnihovnyCz\Controller
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

use KnihovnyCz\RecordDriver\SolrDefault;
use KnihovnyCz\Ziskej\ZiskejMvs;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;
use Mzk\ZiskejApi\RequestModel\Message;
use VuFind\Exception\LibraryCard;
use VuFind\Log\LoggerAwareTrait;

/**
 * Class MyResearchZiskejController
 *
 * @category VuFind
 * @package  KnihovnyCz\Controller
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class MyResearchZiskejController extends AbstractBase
{
    use LoggerAwareTrait;

    /**
     * Ziskej tickets page
     *
     * @return \Laminas\View\Model\ViewModel
     *
     * @throws \Http\Client\Exception
     */
    public function homeAction(): ViewModel
    {
        $view = $this->createViewModel();

        try {
            $user = $this->getUser();
            if (!$user) {
                if ($this->params()->fromQuery('redirect', true)) {
                    $this->setFollowupUrlToReferer();
                }
                return $this->forwardTo('MyResearch', 'Login');
            }
            $view->setVariable('user', $user);

            $userCard = $user->getCardByCatName($user->cat_username);
            if (!$userCard) {
                return $view;
            }
            $view->setVariable('userCard', $userCard);
            if (!$userCard->eppn) {
                return $view;
            }

            /** @var \KnihovnyCz\Ziskej\ZiskejMvs $cpkZiskejMvs */
            $cpkZiskejMvs = $this->serviceLocator->get(ZiskejMvs::class);
            $isZiskejModeEnabled = $cpkZiskejMvs->isEnabled();
            $view->setVariable('isZiskejModeEnabled', $isZiskejModeEnabled);
            if (!$isZiskejModeEnabled) {
                return $view;
            }

            /** @var \Mzk\ZiskejApi\Api $ziskejApi */
            $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

            /* is library in ziskej */
            $isLibraryInZiskej = $this->isLibraryInZiskej($ziskejApi, $userCard->home_library);
            $view->setVariable('isLibraryInZiskej', $isLibraryInZiskej);
            if (!$isLibraryInZiskej) {
                return $view;
            }

            $reader = $ziskejApi->getReader($userCard->eppn);
            if (!$reader || !$reader->isActive()) {
                return $view;
            }
            $view->setVariable('reader', $reader);

            $tickets = [];
            foreach ($ziskejApi->getTickets($userCard->eppn)->getAll() as $ticket) {
                $tickets[$ticket->getId()] = [
                    'ticket' => $ticket,
                    'record' => $this->getRecord($ticket->getDocumentId()),
                ];
            }
            $view->setVariable('tickets', $tickets);
            return $view;
        } catch (\Exception $e) {
            $this->logError('Unexpected ' . get_class($e) . ': ' . $e->getMessage());
            return $view;
        }
    }

    /**
     * Ziskej ticket detail
     *
     * @return \Laminas\View\Model\ViewModel
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \VuFind\Exception\LibraryCard
     */
    public function ticketAction(): ViewModel
    {
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

        /** @var \Mzk\ZiskejApi\Api $ziskejApi */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $ticket = $ziskejApi->getTicket($userCard->eppn, $ticketId);
        $messages = $ziskejApi->getMessages($userCard->eppn, $ticketId);
        $driver = $this->getRecord($ticket->getDocumentId());

        return $this->createViewModel(
            compact(
                'userCard', 'ticket', 'messages', 'driver'
            )
        );
    }

    /**
     * Cancel Ziskej ticket
     *
     * @return \Laminas\Http\Response
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \VuFind\Exception\LibraryCard
     */
    public function ticketCancelAction(): Response
    {
        $eppnDomain = $this->params()->fromRoute('eppnDomain');
        $ticketId = $this->params()->fromRoute('ticketId');

        $user = $this->getUser();
        if (!$user) {
            //$this->flashExceptions($this->flashMessenger());  //@todo
            return $this->forceLogin();
        }

        $userCard = $user->getCardByEppnDomain($eppnDomain);
        if (!$userCard || !$userCard->eppn) {
            throw new LibraryCard('Library Card Not Found');
        }

        /** @var \Mzk\ZiskejApi\Api $ziskejApi */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $deleted = $ziskejApi->cancelTicket($userCard->eppn, $ticketId);

        if ($deleted) {
            $this->flashMessenger()->addMessage('Ziskej::message_ziskej_order_cancel_success', 'success');
        } else {
            $this->flashMessenger()->addMessage('Ziskej::message_ziskej_order_cancel_fail', 'error');
        }

        return $this->redirect()->toRoute('myresearch-ziskej-ticket', ['eppnDomain' => $eppnDomain, 'ticketId' => $ticketId]);
    }

    /**
     * Send form: Create new message
     *
     * @return \Laminas\Http\Response
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \VuFind\Exception\LibraryCard
     */
    public function ticketMessageAction(): Response
    {
        $eppnDomain = $this->params()->fromRoute('eppnDomain');
        $ticketId = $this->params()->fromRoute('ticketId');

        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute(
                'myresearch-ziskej-ticket', [
                    'eppnDomain' => $eppnDomain,
                    'ticketId' => $ticketId,
                ]
            );
        }

        $user = $this->getUser();
        if (!$user) {
            //$this->flashExceptions($this->flashMessenger());  //@todo
            return $this->forceLogin();
        }

        $userCard = $user->getCardByEppnDomain($eppnDomain);
        if (!$userCard || !$userCard->eppn) {
            throw new LibraryCard('Library Card Not Found');
        }

        $ticketMessage = $this->params()->fromPost('ticketMessage');
        if (empty($ticketMessage)) {
            $this->flashMessenger()->addMessage('Ziskej::message_ziskej_message_required_ticketMessage', 'error');

            return $this->redirect()->toRoute(
                'myresearch-ziskej-ticket', [
                    'eppnDomain' => $eppnDomain,
                    'ticketId' => $ticketId,
                ]
            );
        }

        /** @var \Mzk\ZiskejApi\Api $ziskejApi */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $message = new Message($ticketMessage);

        $creaded = $ziskejApi->createMessage($userCard->eppn, $ticketId, $message);
        if ($creaded) {
            $this->flashMessenger()->addMessage('Ziskej::message_ziskej_message_send_success', 'success');
        } else {
            $this->flashMessenger()->addMessage('Ziskej::message_ziskej_message_send_fail', 'error');
        }

        return $this->redirect()->toRoute(
            'myresearch-ziskej-ticket', [
                'eppnDomain' => $eppnDomain,
                'ticketId' => $ticketId,
            ]
        );
    }

    /**
     * @param string $documentId
     *
     * @return \KnihovnyCz\RecordDriver\SolrDefault|null
     *
     * @throws \Exception
     */
    private function getRecord(string $documentId): ?SolrDefault
    {
        $recordLoader = $this->getRecordLoader();

        return $recordLoader->load($documentId);
    }

    /**
     * @param \Mzk\ZiskejApi\Api $ziskejApi
     * @param string|null        $libraryCode
     *
     * @return bool
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     */
    private function isLibraryInZiskej(\Mzk\ZiskejApi\Api $ziskejApi, ?string $libraryCode): bool
    {
        if (empty($libraryCode)) {
            return false;
        }

        /** @var \KnihovnyCz\ILS\Driver\MultiBackend $multiBackend */
        $multiBackend = $this->getILS()->getDriver();

        $sigla = $multiBackend->sourceToSigla($libraryCode);
        return $sigla && $ziskejApi->getLibrary($sigla);
    }
}
