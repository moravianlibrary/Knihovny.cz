<?php

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

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\Stdlib\ResponseInterface as Response;
use Laminas\Validator\EmailAddress;
use Laminas\View\Model\ViewModel;
use Mzk\ZiskejApi\Enum\TicketDataSource;
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
            return $this->redirectToTabEdd();
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
                'ZiskejEdd::error_eppn_missing',
                'error'
            );
            return $this->redirectToTabEdd();
        }

        /**
         * Email address
         *
         * @var string $email
         */
        $email = $this->params()->fromPost('email');
        if (!$email) {
            $this->flashMessenger()->addMessage(
                'ZiskejEdd::error_email_missing',
                'error'
            );
            return $this->redirectToTabEdd();
        }

        $emailValidator = new EmailAddress();
        if (!$emailValidator->isValid($email)) {
            $this->flashMessenger()->addMessage(
                'ZiskejEdd::error_email_wrong',
                'error'
            );
            return $this->redirectToTabEdd();
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
                            'msg' => 'ZiskejEdd::error_max_total_pages_exceeded',
                            'tokens' => [
                                '%%limit%%' =>
                                    ZiskejSettings::EDD_SELECTION_MAX_PAGES,
                            ],
                        ],
                        'error'
                    );
                    return $this->redirectToTabEdd();
                }
            }
        }

        if (!$this->params()->fromPost('is_conditions')) {
            $this->flashMessenger()->addMessage(
                'ZiskejEdd::error_is_conditions',
                'error'
            );
            return $this->redirectToTabEdd();
        }

        if (!$this->params()->fromPost('is_price')) {
            $this->flashMessenger()->addMessage(
                'ZiskejEdd::error_is_price',
                'error'
            );
            return $this->redirectToTabEdd();
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
                'ZiskejEdd::error_account_not_active',
                'error'
            );
            return $this->redirectToTabEdd();
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
                'ZiskejEdd::failure_order_finished',
                'error'
            );
            $this->flashMessenger()->addMessage(
                $e->getMessage(),
                'error'
            );
            return $this->redirectToTabEdd();
        }

        if (!$ziskejReader->isActive) {
            $this->flashMessenger()->addMessage(
                'ZiskejEdd::error_account_not_active',
                'warning'
            );
            return $this->redirectToTabEdd();
        }

        $ticketRequest = new TicketEddRequest(
            ticketDocDataSource: TicketDataSource::AUTO,
            eddSubtype: TicketEddSubtype::from($this->params()->fromPost('edd_subtype')),
            docTitleIn: $this->params()->fromPost('doc_title_in'),
            docTitle: $this->params()->fromPost('doc_title'),
            documentId: $this->params()->fromPost('doc_id'),
            documentAltIds: $this->params()->fromPost('doc_alt_ids'),
            docNumberYear: $this->params()->fromPost('doc_number_year'),
            docNumberPyear: $this->params()->fromPost('doc_number_pyear'),
            docNumberPnumber: $this->params()->fromPost('doc_number_pnumber'),
            docVolume: $this->params()->fromPost('doc_volume'),
            pagesFrom: (int)$this->params()->fromPost('pages_from'),
            pagesTo: (int)$this->params()->fromPost('pages_to'),
            docAuthor: $this->params()->fromPost('doc_author'),
            docIssuer: $this->params()->fromPost('doc_issuer'),
            docISSN: $this->params()->fromPost('doc_issn'),
            docISBN: $this->params()->fromPost('doc_isbn'),
            docCitation: $this->params()->fromPost('doc_citation'),
            docNote: $this->params()->fromPost('doc_note')
        );

        try {
            /**
             * Ticket response model
             *
             * @var \Mzk\ZiskejApi\ResponseModel\TicketEdd $ticket
             */
            $ticket = $ziskejApi->createTicket($eppn, $ticketRequest);

            if (!$ticket) {
                $this->flashMessenger()->addMessage(
                    'ZiskejEdd::failure_order_finished',
                    'error'
                );
                return $this->redirectToTabEdd();
            }

            $this->flashMessenger()->addMessage(
                'ZiskejEdd::success_order_finished',
                'success'
            );
            return $this->redirect()->toRoute(
                'myresearch-ziskej-edd-ticket',
                [
                    'eppnDomain' => $userCard->getEppnDomain(),
                    'ticketId' => $ticket->id,
                ]
            );
        } catch (\Mzk\ZiskejApi\Exception\ApiResponseException $e) {
            $this->flashMessenger()->addMessage(
                'ZiskejEdd::failure_order_finished',
                'error'
            );
            $this->flashMessenger()->addMessage(
                $e->getMessage(),
                'error'
            );
            return $this->redirectToTabEdd();
        }
    }

    /**
     * Shortcut redirect to Ziskej EDD tab
     *
     * @return \Laminas\Http\Response
     */
    protected function redirectToTabEdd(): \Laminas\Http\Response
    {
        return $this->redirectToRecord('#ziskejedd', 'ZiskejEdd');
    }
}
