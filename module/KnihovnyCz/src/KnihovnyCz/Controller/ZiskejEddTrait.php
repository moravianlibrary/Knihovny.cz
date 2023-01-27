<?php
declare(strict_types=1);
/**
 * Ziskej EDD trait
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
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
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Site
 */
namespace KnihovnyCz\Controller;

use Laminas\Stdlib\ResponseInterface as Response;
use Laminas\Validator\EmailAddress;
use Laminas\View\Model\ViewModel;
use Mzk\ZiskejApi\Enum\TicketEddDocDataSource;
use Mzk\ZiskejApi\Enum\TicketEddSubtype;
use Mzk\ZiskejApi\Enum\ZiskejSettings;
use Mzk\ZiskejApi\RequestModel\Reader;
use Mzk\ZiskejApi\RequestModel\TicketEddRequest;
use VuFind\Exception\LibraryCard;

/**
 * Ziskej EDD trait
 *
 * @category VuFind
 * @package  KnihovnyCz\Controller
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Site
 */
trait ZiskejEddTrait
{
    /**
     * Ziskej EDD order action
     *
     * @return \Laminas\View\Model\ViewModel
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \VuFind\Exception\LibraryCard
     */
    public function ziskejEddOrderAction(): ViewModel
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

        $userCard = $user->getCardByEppnDomain(
            $this->params()->fromRoute('eppnDomain')
        );
        if (!$userCard) {
            throw new LibraryCard('Library Card Not Found');
        }

        $user->activateCardByPrefix($userCard->card_name);

        $patron = $this->catalogLogin();
        if (!is_array($patron)) {
            throw new LibraryCard('ILS connection failed');
        }

        /**
         * Ziskej API connector
         *
         * @var \Mzk\ZiskejApi\Api $ziskejApi
         */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $ziskejReader = $userCard->eppn
            ? $ziskejApi->getReader($userCard->eppn)
            : null;

        $view = $this->createViewModel(
            [
                'user' => $user,
                'userCard' => $userCard,
                'patron' => $patron,
                'ziskejReader' => $ziskejReader,
                'serverName' => $this->getRequest()->getServer()->SERVER_NAME,
                'entityId' =>
                    $this->getRequest()->getServer('Shib-Identity-Provider'),
            ]
        );
        $view->setTemplate('record/ziskej-edd-order');

        // getDeduplicatedRecordIds has to be placed after create view model:
        $view->setVariable(
            'dedupedRecordIds',
            $this->driver->tryMethod('getDeduplicatedRecordIds', [], [])
        );

        return $view;
    }

    /**
     * Send Ziskej EDD order form
     *
     * @return \Laminas\Stdlib\ResponseInterface
     *
     * @throws \Consistence\Enum\InvalidEnumValueException
     * @throws \Consistence\InvalidArgumentTypeException
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiException
     * @throws \Mzk\ZiskejApi\Exception\ApiInputException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \VuFind\Exception\LibraryCard
     */
    public function ziskejEddOrderPostAction(): Response
    {
        if (!$this->getRequest()->isPost()) {
            return $this->_redirectToTabEdd();
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
            $this->flashMessenger()->addMessage(
                'Ziskej::error_eppn_missing',
                'error'
            );
            return $this->_redirectToTabEdd();
        }

        /**
         * Email address
         *
         * @var string $email
         */
        $email = $this->params()->fromPost('email');
        if (!$email) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_email_missing',
                'error'
            );
            return $this->_redirectToTabEdd();
        }

        $emailValidator = new EmailAddress();
        if (!$emailValidator->isValid($email)) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_email_wrong',
                'error'
            );
            return $this->_redirectToTabEdd();
        }

        $pagesFrom = $this->params()->fromPost('pages_from');
        $pagesTo = $this->params()->fromPost('pages_to');
        $eddSubtype = $this->params()->fromPost('edd_subtype');

        if ($eddSubtype === TicketEddSubtype::SELECTION) {
            if (!empty($pagesFrom) && !empty($pagesTo)) {
                $totalPages = ((int)$pagesTo - (int)$pagesFrom) + 1;
                if ($totalPages > ZiskejSettings::EDD_SELECTION_MAX_PAGES) {
                    $this->flashMessenger()->addMessage(
                        [
                            'msg' => 'Ziskej::error_max_total_pages_exceeded',
                            'tokens' => [
                                '%%limit%%' =>
                                    ZiskejSettings::EDD_SELECTION_MAX_PAGES
                            ],
                        ],
                        'error'
                    );
                    return $this->_redirectToTabEdd();
                }
            }
        }

        if (!$this->params()->fromPost('is_conditions')) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_is_conditions',
                'error'
            );
            return $this->_redirectToTabEdd();
        }

        if (!$this->params()->fromPost('is_price')) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_is_price',
                'error'
            );
            return $this->_redirectToTabEdd();
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
                'error'
            );
            return $this->_redirectToTabEdd();
        }

        $user->activateCardByPrefix($userCard->card_name);

        $patron = $this->catalogLogin();

        $requestReader = new Reader(
            !empty($patron['firstname']) ? $patron['firstname'] : '–',
            !empty($patron['lastname']) ? $patron['lastname'] : '–',
            $email,
            $multibackend->sourceToSigla($userCard->home_library) ?? '',
            true,
            true,
            $userCard->cat_username
        );

        try {
            $ziskejReader = $ziskejApi->getReader($userCard->eppn)
                ? $ziskejApi->updateReader($userCard->eppn, $requestReader)
                : $ziskejApi->createReader($userCard->eppn, $requestReader);
        } catch (\Mzk\ZiskejApi\Exception\ApiResponseException $e) {
            $this->flashMessenger()->addMessage(
                'Ziskej::failure_order_finished',
                'error'
            );
            $this->flashMessenger()->addMessage(
                $e->getMessage(),
                'error'
            );
            return $this->_redirectToTabEdd();
        }

        if (!$ziskejReader->isActive()) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_account_not_active',
                'warning'
            );
            return $this->_redirectToTabEdd();
        }

        $ticketRequest = new TicketEddRequest(
            TicketEddDocDataSource::AUTO,
            $this->params()->fromPost('edd_subtype'),
            $this->params()->fromPost('doc_title_in'),
            $this->params()->fromPost('doc_title'),
            $this->params()->fromPost('doc_id')
        );
        $ticketRequest->setDocumentAltIds($this->params()->fromPost('doc_alt_ids'));
        $ticketRequest->setDocCitation($this->params()->fromPost('doc_citation'));
        $ticketRequest->setDocAuthor($this->params()->fromPost('doc_author'));
        $ticketRequest->setDocVolume($this->params()->fromPost('doc_volume'));
        $ticketRequest->setDocIssuer($this->params()->fromPost('doc_issuer'));
        $ticketRequest->setPagesFrom((int)$this->params()->fromPost('pages_from'));
        $ticketRequest->setPagesTo((int)$this->params()->fromPost('pages_to'));
        $ticketRequest->setDocISSN($this->params()->fromPost('doc_issn'));
        $ticketRequest->setDocISBN($this->params()->fromPost('doc_isbn'));
        $ticketRequest->setDocNumberYear(
            $this->params()->fromPost('doc_number_year')
        );
        $ticketRequest->setDocNumberPyear(
            $this->params()->fromPost('doc_number_pyear')
        );
        $ticketRequest->setDocNumberPnumber(
            $this->params()->fromPost('doc_number_pnumber')
        );
        $ticketRequest->setDocNote($this->params()->fromPost('doc_note'));
        $ticketRequest->setDateRequested(new \DateTimeImmutable('+3 day'));

        try {
            $ticket = $ziskejApi->createTicket($eppn, $ticketRequest);

            if (!$ticket) {
                $this->flashMessenger()->addMessage(
                    'Ziskej::failure_order_finished',
                    'error'
                );
                return $this->_redirectToTabEdd();
            }

            $this->flashMessenger()->addMessage(
                'Ziskej::success_order_finished',
                'success'
            );
            return $this->redirect()->toRoute(
                'myresearch-ziskej-edd-ticket',
                [
                    'eppnDomain' => $userCard->getEppnDomain(),
                    'ticketId' => $ticket->getId(),
                ]
            );
        } catch (\Mzk\ZiskejApi\Exception\ApiResponseException $e) {
            $this->flashMessenger()->addMessage(
                'Ziskej::failure_order_finished',
                'error'
            );
            $this->flashMessenger()->addMessage(
                $e->getMessage(),
                'error'
            );
            return $this->_redirectToTabEdd();
        }
    }

    /**
     * Shortcut redirect to Ziskej EDD tab
     *
     * @return \Laminas\Http\Response
     */
    private function _redirectToTabEdd(): \Laminas\Http\Response
    {
        return $this->redirectToRecord('#ziskejedd', 'ZiskejEdd');
    }
}
