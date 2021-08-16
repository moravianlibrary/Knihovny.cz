<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use VuFind\Controller\AbstractBase;
use VuFind\Exception\LibraryCard;

class ZiskejController extends AbstractBase
{
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

        /** @var \KnihovnyCz\Db\Row\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }

        /** @var \KnihovnyCz\Db\Row\UserCard $userCard */
        $userCard = $user->getCardByEppnDomain($eppnDomain);
        if (!$userCard || $userCard->eppn) {
            throw new LibraryCard('Library Card Not Found');
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
