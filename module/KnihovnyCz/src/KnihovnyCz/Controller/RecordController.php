<?php
declare(strict_types=1);

/**
 * Class RecordController
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
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Stdlib\ResponseInterface as Response;
use Laminas\View\Model\ViewModel;
use Mzk\ZiskejApi\RequestModel\Reader;
use Mzk\ZiskejApi\RequestModel\Ticket;
use VuFind\Exception\LibraryCard;

/**
 * Class RecordController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class RecordController extends \VuFind\Controller\RecordController
{
    /**
     * Dispatch a request
     *
     * @param Request       $request  Http request
     * @param null|Response $response Http response
     *
     * @return Response|mixed
     */
    public function dispatch(Request $request, Response $response = null)
    {
        $id = $this->params()->fromRoute('id');
        if (str_starts_with($id, 'library')) {
            return $this->redirect()->toRoute(
                'search2record',
                $this->params()->fromRoute()
            );
        }
        return parent::dispatch($request, $response);
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

        /**
         * User
         *
         * @var ?\KnihovnyCz\Db\Row\User $user
         */
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }

        /**
         * Ziskej API connector
         *
         * @var \Mzk\ZiskejApi\Api $ziskejApi
         */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $eppnDomain = $this->params()->fromRoute('eppnDomain');

        $userCard = $user->getCardByEppnDomain($eppnDomain);
        if (!$userCard) {
            throw new LibraryCard('Library Card Not Found');
        }
        $eppn = $userCard->eppn;
        $ziskejReader = $eppn ? $ziskejApi->getReader($eppn) : null;

        $view = $this->createViewModel(
            [
                'user' => $user,
                'userCard' => $userCard, //@todo if firstname and lastname is empty
                'ziskejReader' => $ziskejReader,
                'serverName' => $this->getRequest()->getServer()->SERVER_NAME,
                'entityId' =>
                    $this->getRequest()->getServer('Shib-Identity-Provider'),
            ]
        );
        $view->setTemplate('record/ziskej-order');

        // getDeduplicatedRecordIds has to be placed after create view model:
        $view->setVariable(
            'dedupedRecordIds',
            $this->driver->tryMethod('getDeduplicatedRecordIds', [], [])
        );

        return $view;
    }

    /**
     * Create Získej order/ticket
     *
     * @return \Laminas\Stdlib\ResponseInterface
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiException
     * @throws \Mzk\ZiskejApi\Exception\ApiInputException
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \VuFind\Exception\LibraryCard
     */
    public function ziskejOrderPostAction(): Response
    {
        //@todo try/catch

        if (!$this->getRequest()->isPost()) {
            return $this->redirectToRecord('', 'Ziskej');
        }

        /**
         * User
         *
         * @var ?\KnihovnyCz\Db\Row\User $user
         */
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }

        /**
         * Ziskej API connector
         *
         * @var \Mzk\ZiskejApi\Api $ziskejApi
         */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        /**
         * EduPersonPrincipalName shibboleth attribute
         *
         * @var string $eppn
         */
        $eppn = $this->params()->fromPost('eppn');
        if (!$eppn) {
            //@todo
        }

        /**
         * Email address
         *
         * @var string $email
         */
        $email = $this->params()->fromPost('email');
        if (!$email) {
            //@todo
        }
        //@todo check email format

        if (!$this->params()->fromPost('is_conditions')) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_is_conditions',
                'error'
            );
            return $this->redirectToRecord('', 'Ziskej');
        }

        if (!$this->params()->fromPost('is_price')) {
            $this->flashMessenger()->addMessage('Ziskej::error_is_price', 'error');
            return $this->redirectToRecord('', 'Ziskej');
        }

        /**
         * Multibackend ILS driver
         *
         * @var \KnihovnyCz\ILS\Driver\MultiBackend $multibackend
         */
        $multibackend = $this->getILS()->getDriver();

        $userCard = $user->getCardByEppn($eppn);
        if (!$userCard) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_account_not_active',
                'warning'
            );
            return $this->redirectToRecord('', 'Ziskej');
        }

        $responseReader = new Reader(
            $user->firstname,
            $user->lastname,
            $email,
            $multibackend->sourceToSigla($userCard->home_library) ?? '',
            true,
            true,
            $userCard->cat_username
        );

        $saveFunction = 'createReader';
        $eppn = $userCard->eppn;
        if ($eppn && $ziskejApi->getReader($eppn)) {
            $saveFunction = 'updateReader';
        }
        $ziskejReader = $ziskejApi->$saveFunction($userCard->eppn, $responseReader);

        if (!$ziskejReader->isActive()) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_account_not_active',
                'warning'
            );
            //@todo next step
            return $this->redirectToRecord('', 'Ziskej');
        }

        $ticketNew = new Ticket($this->params()->fromPost('doc_id'));
        $ticketNew->setDocumentAltIds($this->params()->fromPost('doc_alt_ids'));
        $ticketNew->setNote($this->params()->fromPost('text'));

        $ticket = null;
        if ($eppn) {
            $ticket = $ziskejApi->createTicket($eppn, $ticketNew);
        }

        if ($ticket) {
            $this->flashMessenger()->addMessage(
                'Ziskej::success_order_finished',
                'success'
            );

            return $this->redirect()->toRoute(
                'ziskej-order-finished',
                [
                    'eppnDomain' => $userCard->getEppnDomain(),
                    'ticketId' => $ticket->getId(),
                ]
            );
        }
        $this->flashMessenger()->addMessage(
            'Ziskej::success_order_finished',
            'warning'
        );
        return $this->redirect()->toRoute(
            'ziskej-order-finished',
            [
                'eppnDomain' => $userCard->getEppnDomain(),
            ]
        );
    }
}
