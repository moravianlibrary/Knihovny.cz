<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use KnihovnyCz\Controller\Exception\TicketNotFoundException;
use VuFind\Controller\AbstractBase;

class ZiskejController extends AbstractBase
{
    /**
     * Ziskej order finished page
     *
     * @return \Laminas\View\Model\ViewModel|mixed|void
     *
     * @throws \Http\Client\Exception
     * @throws \KnihovnyCz\Controller\Exception\TicketNotFoundException
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \VuFind\Exception\LibraryCard
     */
    public function finishedAction(){
        //@todo try/catch

        $eppnDomain = $this->params()->fromRoute('eppnDomain');
        if (!$eppnDomain) {
            throw new TicketNotFoundException('The requested order was not found');
        }

        $ticketId = $this->params()->fromRoute('ticketId');
        if (!$ticketId) {
            throw new TicketNotFoundException('The requested order was not found');
        }

        /** @var \KnihovnyCz\Db\Row\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }

        /** @var \KnihovnyCz\Db\Row\UserCard $userCard */
        $userCard = $user->getCardByEppnDomain($eppnDomain);
        if (!$userCard) {
            exit('no user card');   //@todo if no userCard
        }

        if (!$userCard->eppn) {
            //@todo
        }

        /** @var \Mzk\ZiskejApi\Api $ziskejApi */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $ticket = $ziskejApi->getTicket($userCard->eppn, $ticketId);

        $view = $this->createViewModel();
        $view->userCard = $userCard;
        $view->ticket = $ticket;
        return $view;
    }
}
