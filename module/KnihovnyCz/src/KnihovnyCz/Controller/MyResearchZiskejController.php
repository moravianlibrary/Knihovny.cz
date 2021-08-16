<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use KnihovnyCz\Controller\Exception\TicketNotFoundException;
use KnihovnyCz\Db\Row\UserCard;
use KnihovnyCz\RecordDriver\SolrLocal;
use KnihovnyCz\Ziskej\ZiskejMvs;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;
use Mzk\ZiskejApi\RequestModel\Message;
use VuFind\Controller\AbstractBase;
use KnihovnyCz\Db\Row\User;
use VuFind\Log\LoggerAwareTrait;

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
            /** @var \KnihovnyCz\Db\Row\User $user */
            $user = $this->getUser();
            if (!$user) {
                if ($this->params()->fromQuery('redirect', true)) {
                    $this->setFollowupUrlToReferer();
                }
                return $this->forwardTo('MyResearch', 'Login');
            }
            $view->setVariable('user', $user);

            $userCard = $user->getCardByName($user->cat_username);
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
     * @throws \KnihovnyCz\Controller\Exception\TicketNotFoundException
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     */
    public function ticketAction(): ViewModel
    {
        $eppnDomain = $this->params()->fromRoute('eppnDomain');
        if (!$eppnDomain) {
            throw new TicketNotFoundException('The requested order was not found');
        }

        $ticketId = $this->params()->fromRoute('ticketId');
        if (!$ticketId) {
            throw new TicketNotFoundException('The requested order was not found');
        }

        $user = $this->getAuthManager()->isLoggedIn();
        if (!$user) {
            //$this->flashExceptions($this->flashMessenger());  //@todo
            return $this->forceLogin();
        }

        $userCard = $user->getCardByEppnDomain($eppnDomain);
        if (!$userCard || !$userCard->eppn) {
            throw new TicketNotFoundException('The requested order was not found');
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
     * @return \Laminas\Http\Response
     *
     * @throws \Http\Client\Exception
     * @throws \KnihovnyCz\Controller\Exception\TicketNotFoundException
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     */
    public function ticketCancelAction(): Response
    {
        $eppnDomain = $this->params()->fromRoute('eppnDomain');
        if (!$eppnDomain) {
            throw new TicketNotFoundException('The requested order was not found');
        }

        $ticketId = $this->params()->fromRoute('ticketId');
        if (!$ticketId) {
            throw new TicketNotFoundException('The requested order was not found');
        }

        $user = $this->getAuthManager()->isLoggedIn();
        if (!$user) {
            //$this->flashExceptions($this->flashMessenger());  //@todo
            return $this->forceLogin();
        }

        $userCard = $user->getCardByEppnDomain($eppnDomain);
        if (!$userCard || !$userCard->eppn) {
            throw new TicketNotFoundException('The requested order was not found');
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

    public function ticketMessageAction(): Response
    {
        //@todo if method != POST

        $eppnDomain = $this->params()->fromRoute('eppnDomain');
        if (!$eppnDomain) {
            throw new TicketNotFoundException('The requested order was not found');
        }

        $ticketId = $this->params()->fromRoute('ticketId');
        if (!$ticketId) {
            throw new TicketNotFoundException('The requested order was not found');
        }

        /** @var \KnihovnyCz\Db\Row\User $user */
        $user = $this->getAuthManager()->isLoggedIn();
        if (!$user) {
            //$this->flashExceptions($this->flashMessenger());  //@todo
            return $this->forceLogin();
        }

        /** @var \KnihovnyCz\Db\Row\UserCard $userCard */
        $userCard = $user->getCardByEppnDomain($eppnDomain);
        if (!$userCard || !$userCard->eppn) {
            throw new TicketNotFoundException('The requested order was not found');
        }

        $ticketMessage = $this->params()->fromPost('ticketMessage');
        if (empty($ticketMessage)) {
            $this->flashMessenger()->addMessage('Ziskej::message_ziskej_message_required_ticketMessage', 'error');

            return $this->redirect()->toRoute('myresearch-ziskej-ticket', [
                'eppnDomain' => $eppnDomain,
                'ticketId' => $ticketId,
            ]);
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

        return $this->redirect()->toRoute('myresearch-ziskej-ticket', [
            'eppnDomain' => $eppnDomain,
            'ticketId' => $ticketId,
        ]);
    }

    private function getRecord(string $documentId): ?SolrLocal
    {
        $recordLoader = $this->getRecordLoader();

        /** @var SolrLocal|null $record */
        $record = $recordLoader->load($documentId);
        return $record;
    }

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
