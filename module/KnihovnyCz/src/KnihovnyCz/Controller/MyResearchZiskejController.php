<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use KnihovnyCz\Controller\Exception\TicketNotFoundException;
use KnihovnyCz\Db\Row\UserCard;
use KnihovnyCz\RecordDriver\SolrLocal;
use KnihovnyCz\Ziskej\ZiskejMvs;
use Laminas\View\Model\ViewModel;
use VuFind\Controller\AbstractBase;
use KnihovnyCz\Db\Row\User;

class MyResearchZiskejController extends AbstractBase
{

    public function homeAction(): ViewModel
    {
//      try {   //@todo!!!

        /** @var \KnihovnyCz\Ziskej\ZiskejMvs $cpkZiskejMvs */
        $cpkZiskejMvs = $this->serviceLocator->get(ZiskejMvs::class);
        if (!$cpkZiskejMvs->isEnabled()) {
            echo '!$cpkZiskejMvs->isEnabled()'; //@todo!!!
        }

        /** @var \KnihovnyCz\Db\Row\User $user */
        $user = $this->getUser();
        if (!$user) {
            echo '!$user';  //@todo!!!
        }

        /** @var \Mzk\ZiskejApi\Api $ziskejApi */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $userCard = $this->getCardByName($user, $user->cat_username);
        if (!$userCard->eppn) {
            echo '!$userCard->eppn';  //@todo!!!
        }

        $reader = null;
        $tickets = [];
        if ($this->isLibraryInZiskej($ziskejApi, $userCard->home_library)) {
            $reader = $ziskejApi->getReader($userCard->eppn);

            if ($reader && $reader->isActive()) {
                foreach ($ziskejApi->getTickets($userCard->eppn)->getAll() as $ticket) {
                    $tickets[$ticket->getId()] = [
                        'ticket' => $ticket,
                        'record' => $this->getRecord($ticket->getDocumentId()),
                    ];
                }
            }
        }

        return $this->createViewModel(
            compact(
                'userCard', 'reader', 'tickets'
            )
        );
    }

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

        if (!$user = $this->getAuthManager()->isLoggedIn()) {
            //$this->flashExceptions($this->flashMessenger());  //@todo
            return $this->forceLogin();
        }

        $userCard = $this->getCardByEppnDomain($user, $eppnDomain);

        if (!$userCard || !$userCard->eppn) {
            throw new TicketNotFoundException('The requested order was not found');
        }

        /** @var \Mzk\ZiskejApi\Api $ziskejApi */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $ticket = $ziskejApi->getTicket($userCard->eppn, $ticketId);
        $messages = $ziskejApi->getMessages($userCard->eppn, $ticketId);
        $record = $this->getRecord($ticket->getDocumentId());

        return $this->createViewModel(
            compact(
                'userCard', 'ticket', 'messages', 'record'
            )
        );
    }

    private function getRecord(string $documentId): ?SolrLocal
    {
        $recordLoader = $this->getRecordLoader();

        /** @var SolrLocal|null $record */
        $record = $recordLoader->load($documentId);
        return $record;
    }

    private function isLibraryInZiskej(\Mzk\ZiskejApi\Api $ziskejApi, string $libraryCode): bool
    {
        /** @var \KnihovnyCz\ILS\Driver\MultiBackend $multiBackend */
        $multiBackend = $this->getILS()->getDriver();

        $sigla = $multiBackend->sourceToSigla($libraryCode);
        return $sigla && $ziskejApi->getLibrary($sigla);
    }

    /**
     * @param \KnihovnyCz\Db\Row\User $user
     * @param string              $cardName
     *
     * @return \KnihovnyCz\Db\Row\UserCard|null
     * @throws \VuFind\Exception\LibraryCard
     */
    private function getCardByName(User $user, string $cardName): ?UserCard
    {
        //@todo move to class Row\User

        /** @var \KnihovnyCz\Db\Row\UserCard $userCard */
        foreach ($user->getLibraryCards() as $userCard) {
            if ($userCard->card_name === $cardName) {
                return $userCard;
            }
        }

        return null;
    }

    private function getCardByEppnDomain(User $user, string $eppnDomain): ?UserCard
    {
        //@todo move to class Row\User

        /** @var \KnihovnyCz\Db\Row\UserCard $userCard */
        foreach ($user->getLibraryCards() as $userCard) {
            if ($userCard->getEppnDomain() === $eppnDomain) {
                return $userCard;
            }
        }

        return null;
    }
}
