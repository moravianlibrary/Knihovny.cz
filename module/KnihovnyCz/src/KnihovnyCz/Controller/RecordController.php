<?php declare(strict_types=1);


namespace KnihovnyCz\Controller;


use Laminas\Config\Config;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;
use Mzk\ZiskejApi\RequestModel\Reader;
use Mzk\ZiskejApi\RequestModel\Ticket;

class RecordController extends \VuFind\Controller\RecordController
{

    public function __construct(ServiceLocatorInterface $sm, Config $config)
    {
        parent::__construct($sm, $config);
    }

    /**
     * Ziskej order
     *
     * @return \Laminas\View\Model\ViewModel
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \VuFind\Exception\LibraryCard
     */
    public function ziskejOrderAction(): ViewModel
    {
        //@todo try/catch

        /** @var bool|\KnihovnyCz\Db\Row\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }

        /** @var \Mzk\ZiskejApi\Api $ziskejApi */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $eppnDomain = $this->params()->fromRoute('eppnDomain');
        if (!$eppnDomain) {
            exit('no eppn domain'); //@todo
        }

        $userCard = $user->getCardByEppnDomain($eppnDomain);
        if (!$userCard) {
            exit('no user card');   //@todo if no userCard
        }

        $ziskejReader = $ziskejApi->getReader($userCard->eppn);

        $view = $this->createViewModel();
        $view->setTemplate('record/ziskej-order');
        $view->user = $user;
        $view->userCard = $userCard;    //@todo if firstname and lastname is empty
        $view->ziskejReader = $ziskejReader;
        $view->serverName = $this->getRequest()->getServer()->SERVER_NAME;
        $view->entityId = $this->getRequest()->getServer('Shib-Identity-Provider');

        $dedupedRecord = $this->driver->tryMethod('getDeduplicatedRecords', [], []);   // must be placed after create view model
        $view->records = $dedupedRecord;

        return $view;
    }

    /**
     * Ziskej order sended
     *
     * @return \Laminas\Http\Response|mixed
     *
     * @throws \Mzk\ZiskejApi\Exception\ApiException
     * @throws \Mzk\ZiskejApi\Exception\ApiInputException
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \VuFind\Exception\LibraryCard
     * @throws \Http\Client\Exception
     */
    public function ziskejOrderPostAction()
    {
        //@todo try/catch

        if (!$this->getRequest()->isPost()) {
            return $this->redirectToRecord('', 'Ziskej');
        }

        /** @var \KnihovnyCz\Db\Row\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }

        /** @var \Mzk\ZiskejApi\Api $ziskejApi */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        /** @var string $eppn */
        $eppn = $this->params()->fromPost('eppn');
        if (!$eppn) {
            //@todo
        }

        /** @var string $email */
        $email = $this->params()->fromPost('email');
        if (!$email) {
            //@todo
        }
        //@todo check email format

        if (!$this->params()->fromPost('is_conditions')) {
            $this->flashMessenger()->addMessage('ziskej_error_is_conditions', 'error');
            return $this->redirectToRecord('', 'Ziskej');
        }

        if (!$this->params()->fromPost('is_price')) {
            $this->flashMessenger()->addMessage('ziskej_error_is_price', 'error');
            return $this->redirectToRecord('', 'Ziskej');
        }

        /** @var \KnihovnyCz\ILS\Driver\MultiBackend $multibackend */
        $multibackend = $this->getILS()->getDriver();

        $userCard = $user->getCardByEppn($eppn);

        $responseReader = new Reader(
            $user->firstname,
            $user->lastname,
            $email,
            $multibackend->sourceToSigla($user->home_library),
            true,
            true,
            $userCard->cat_username
        );

        if ($ziskejApi->getReader($userCard->eppn)) {
            $ziskejReader = $ziskejApi->updateReader($userCard->eppn, $responseReader);
        } else {
            $ziskejReader = $ziskejApi->createReader($userCard->eppn, $responseReader);
        }

        if (!$ziskejReader->isActive()) {
            $this->flashMessenger()->addMessage('ziskej_error_account_not_active', 'warning');
            //@todo next step
            return $this->redirectToRecord('', 'Ziskej');
        }

        $ticketNew = new Ticket($this->params()->fromPost('doc_id'));
        $ticketNew->setDocumentAltIds($this->params()->fromPost('doc_alt_ids'));
        $ticketNew->setNote($this->params()->fromPost('text'));

        $ticket = $ziskejApi->createTicket($userCard->eppn, $ticketNew);

        $this->flashMessenger()->addMessage('ziskej_success_order_finished', 'success');

        return $this->redirect()->toRoute('ziskejOrderFinished', [
            'eppnDomain' => $userCard->getEppnDomain(),
            'ticketId' => $ticket->getId(),
        ]);
    }

}
