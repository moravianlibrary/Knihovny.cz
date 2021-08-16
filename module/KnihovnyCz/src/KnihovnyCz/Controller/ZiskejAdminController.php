<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use KnihovnyCz\Ziskej\ZiskejMvs;
use KnihovnyCz\Ziskej\ZiskejEdd;
use VuFind\Controller\AbstractBase;

class ZiskejAdminController extends AbstractBase
{

    /**
     * @return \Laminas\Http\Response|\Laminas\View\Model\ViewModel
     *
     * @throws \Http\Client\Exception
     * @throws \VuFind\Exception\LibraryCard
     */
    public function homeAction()
    {
        $view = $this->createViewModel();

        /** @var \KnihovnyCz\Ziskej\ZiskejMvs $cpkZiskejMvs */
        $cpkZiskejMvs = $this->serviceLocator->get(ZiskejMvs::class);

        /** @var \KnihovnyCz\Ziskej\ZiskejEdd $cpkZiskejEdd */
        $cpkZiskejEdd = $this->serviceLocator->get(ZiskejEdd::class);

        if ($this->getRequest()->isPost()) {
            if ($this->getRequest()->getPost('ziskejMvsMode')) {
                $cpkZiskejMvs->setMode($this->getRequest()->getPost('ziskejMvsMode'));
            }
            if ($this->getRequest()->getPost('ziskejEddMode')) {
                $cpkZiskejEdd->setMode($this->getRequest()->getPost('ziskejEddMode'));
            }
            $this->flashMessenger()->addMessage('Ziskej::message_ziskej_mode_saved', 'success');
            return $this->redirect()->refresh();
        }

        if (!$cpkZiskejMvs->isEnabled() && !$cpkZiskejEdd->isEnabled()) {
            return $view;
        }

        /** @var \KnihovnyCz\Db\Row\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $view;
        }
        $view->setVariable('user', $user);

        $userCards = $user->getLibraryCards();

        /** @var \KnihovnyCz\ILS\Driver\MultiBackend $multiBackend */
        $multiBackend = $this->getILS()->getDriver();

        try {
            /** @var \Mzk\ZiskejApi\Api $ziskejApi */
            $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

            /** @var string[] $ziskejLibsCodes */
            $ziskejLibsCodes = [];

            $ziskejLibs = $ziskejApi->getLibrariesAll();
            foreach ($ziskejLibs->getAll() as $ziskejLib) {
                $id = $multiBackend->siglaToSource($ziskejLib->getSigla());
                if (!empty($id)) {
                    $ziskejLibsCodes[] = $id;
                }
            }

            $data = [];

            /** @var \KnihovnyCz\Db\Row\UserCard $userCard */
            foreach ($userCards as $userCard) {
                $eppn = $userCard->eppn;
                if (!$eppn) {
                    continue;
                }

                $inZiskej = in_array($userCard->home_library, $ziskejLibsCodes);
                $data[$eppn]['isLibraryInZiskej'] = $inZiskej;

                if ($inZiskej) {
                    /** @var \Mzk\ZiskejApi\ResponseModel\Reader $ziskejReader */
                    $ziskejReader = $ziskejApi->getReader($eppn);
                    $data[$eppn]['reader'] = $ziskejReader;

                    if ($ziskejReader && $ziskejReader->isActive()) {
                        /** @var \Mzk\ZiskejApi\ResponseModel\TicketsCollection $tickets */
                        $tickets = $ziskejApi->getTickets($eppn)->getAll();
                        $data[$eppn]['tickets'] = [];
                        /** @var \Mzk\ZiskejApi\ResponseModel\Ticket $ticket */
                        foreach ($tickets as $ticket) {
                            $data[$eppn]['tickets'][$ticket->getId()]['ticket'] = $ticket;
                            $data[$eppn]['tickets'][$ticket->getId()]['messages'] = $ziskejApi->getMessages($eppn, $ticket->getId())->getAll();
                        }
                    }
                }
            }

            $view->setVariable('data', $data);
        } catch (\Exception $ex) {
            $this->flashMessenger()->addMessage($ex->getMessage(), 'warning');
            //$this->flashMessenger()->addMessage('Ziskej::warning_api_disconnected', 'warning');    //@todo zapnout na produkci
        }

        return $view;
    }
}
