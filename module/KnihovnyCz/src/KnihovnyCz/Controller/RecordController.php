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

use Laminas\Config\Config;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Stdlib\ResponseInterface as Response;
use Laminas\Validator\EmailAddress;
use Laminas\View\Model\ViewModel;
use Mzk\ZiskejApi\RequestModel\Reader;
use Mzk\ZiskejApi\RequestModel\Ticket;
use VuFind\Exception\LibraryCard;
use VuFind\Validator\CsrfInterface;

/**
 * Class RecordController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 *
 * @method \Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger  flashMessenger
 * @method Plugin\ShortLoans shortLoans() Time slots controller plugin
 */
class RecordController extends \VuFind\Controller\RecordController
{
    /**
     * Redirect library records to library record controller?
     *
     * @var boolean
     */
    protected $redirectToLibrary = false;

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm     Service manager
     * @param Config                  $config VuFind configuration
     */
    public function __construct(ServiceLocatorInterface $sm, Config $config)
    {
        parent::__construct($sm, $config);
        $this->redirectToLibrary = ($config->SearchTabs->Search2 ?? null)
            == "Libraries directory";
    }

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
        if ($this->redirectToLibrary
            && str_starts_with($this->params()->fromRoute('id'), 'library')
        ) {
            return $this->redirect()->toRoute(
                'search2record',
                $this->params()->fromRoute()
            );
        }
        return parent::dispatch($request, $response);
    }

    /**
     * Redirect to local record
     *
     * @return mixed
     */
    public function redirectToLocalRecordAction()
    {
        $record = $this->loadRecord();
        $recordId = $record->getUniqueID();
        $records = $record->tryMethod('getDeduplicatedRecords', [], []);
        if (!empty($records)) {
            $institution = $this->params()->fromQuery('institution');
            $first = reset($records);
            if ($institution !== null && isset($records[$institution])) {
                $first = $records[$institution];
            }
            $recordId = reset($first);
        }
        return $this->redirect()->toRoute(
            'record',
            ['id' => $recordId]
        );
    }

    /**
     * Ziskej order
     *
     * @return \Laminas\View\Model\ViewModel
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \VuFind\Exception\LibraryCard
     */
    public function ziskejOrderPostAction(): Response
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirectToRecord('#ziskejmvs', 'Ziskej');
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
            return $this->redirectToRecord('#ziskejmvs', 'Ziskej');
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
            return $this->redirectToRecord('#ziskejmvs', 'Ziskej');
        }

        $emailValidator = new EmailAddress();
        if (!$emailValidator->isValid($email)) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_email_wrong',
                'error'
            );
            return $this->redirectToRecord('#ziskejmvs', 'Ziskej');
        }

        if (!$this->params()->fromPost('is_conditions')) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_is_conditions',
                'error'
            );
            return $this->redirectToRecord('#ziskejmvs', 'Ziskej');
        }

        if (!$this->params()->fromPost('is_price')) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_is_price',
                'error'
            );
            return $this->redirectToRecord('#ziskejmvs', 'Ziskej');
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
            return $this->redirectToRecord('#ziskejmvs', 'Ziskej');
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
            return $this->redirectToRecord('#ziskejmvs', 'Ziskej');
        }

        if (!$ziskejReader->isActive()) {
            $this->flashMessenger()->addMessage(
                'Ziskej::error_account_not_active',
                'warning'
            );
            return $this->redirectToRecord('#ziskejmvs', 'Ziskej');
        }

        $ticketNew = new Ticket($this->params()->fromPost('doc_id'));
        $ticketNew->setDocumentAltIds($this->params()->fromPost('doc_alt_ids'));
        $ticketNew->setNote($this->params()->fromPost('text'));

        try {
            $ticket = $ziskejApi->createTicket($eppn, $ticketNew);

            if (!$ticket) {
                $this->flashMessenger()->addMessage(
                    'Ziskej::failure_order_finished',
                    'error'
                );
                return $this->redirectToRecord('#ziskejmvs', 'Ziskej');
            }

            $this->flashMessenger()->addMessage(
                'Ziskej::success_order_finished',
                'success'
            );
            return $this->redirect()->toRoute(
                'myresearch-ziskej-ticket',
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
            return $this->redirectToRecord('#ziskejmvs', 'Ziskej');
        }
    }

    /**
     * Short loan action.
     *
     * @return \Laminas\View\Model\ViewModel
     */
    public function shortLoanAction()
    {
        $driver = $this->loadRecord();

        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        // If we're not supposed to be here, give up now!
        $catalog = $this->getILS();
        $checkHolds = $catalog->checkFunction(
            'Holds',
            [
                'id' => $driver->getUniqueID(),
                'patron' => $patron
            ]
        );
        if (!$checkHolds) {
            return $this->redirectToRecord();
        }
        $recordId = $driver->getUniqueID();
        $itemId = $this->params()->fromQuery('item_id');

        // Process form submissions if necessary:
        if (null !== $this->params()->fromPost('placeHold')) {
            $success = $this->shortLoans()->placeHolds(
                $patron,
                $recordId,
                $itemId,
                $this->serviceLocator->get(CsrfInterface::class)
            );
            if ($success) {
                return $this->redirectToRecord();
            }
        }
        $shortLoanInfo = $catalog->getHoldingInfoForItem(
            $patron['id'],
            $recordId,
            $itemId
        );
        $slots = $shortLoanInfo['slots'];
        $view = $this->createViewModel($this->shortLoans()->fillSlots($slots));
        $view->setTemplate('record/shortloan');
        return $view;
    }

    /**
     * Action for dealing with holds.
     *
     * @return mixed
     */
    public function holdAction()
    {
        $driver = $this->loadRecord();

        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        // If we're not supposed to be here, give up now!
        $catalog = $this->getILS();
        $checkHolds = $catalog->checkFunction(
            'Holds',
            [
                'id' => $driver->getUniqueID(),
                'patron' => $patron
            ]
        );
        if (!$checkHolds) {
            return $this->redirectToRecord();
        }

        // Do we have valid information?
        // Sets $this->logonURL and $this->gatheredDetails
        $gatheredDetails = $this->holds()->validateRequest($checkHolds['HMACKeys']);
        if (!$gatheredDetails) {
            return $this->redirectToRecord();
        }

        // Block invalid requests:
        $validRequest = $catalog->checkRequestIsValid(
            $driver->getUniqueID(),
            $gatheredDetails,
            $patron
        );
        if ((is_array($validRequest) && !$validRequest['valid']) || !$validRequest) {
            $this->flashMessenger()->addErrorMessage(
                is_array($validRequest)
                    ? $validRequest['status'] : 'hold_error_blocked'
            );
            return $this->redirectToRecord('#top');
        }

        // Send various values to the view so we can build the form:
        $requestGroups = $catalog->checkCapability(
            'getRequestGroups',
            [$driver->getUniqueID(), $patron, $gatheredDetails]
        ) ? $catalog->getRequestGroups(
            $driver->getUniqueID(),
            $patron,
            $gatheredDetails
        ) : [];
        $extraHoldFields = isset($checkHolds['extraHoldFields'])
            ? explode(":", $checkHolds['extraHoldFields']) : [];

        $requestGroupNeeded = in_array('requestGroup', $extraHoldFields)
            && !empty($requestGroups)
            && (empty($gatheredDetails['level'])
                || ($gatheredDetails['level'] != 'copy'
                    || count($requestGroups) > 1));

        $pickupDetails = $gatheredDetails;
        if (!$requestGroupNeeded && !empty($requestGroups)
            && count($requestGroups) == 1
        ) {
            // Request group selection is not required, but we have a single request
            // group, so make sure pickup locations match with the group
            $pickupDetails['requestGroupId'] = $requestGroups[0]['id'];
        }
        $pickup = $catalog->getPickUpLocations($patron, $pickupDetails);
        $orderInQueue = $catalog->checkCapability(
            'getHoldOrderInQueue',
            [$patron, $gatheredDetails]
        ) ?
            $catalog->getHoldOrderInQueue($patron, $gatheredDetails) : 0;

        // Process form submissions if necessary:
        if (null !== $this->params()->fromPost('placeHold')) {
            // If the form contained a pickup location, request group, start date or
            // required by date, make sure they are valid:
            $validGroup = $this->holds()->validateRequestGroupInput(
                $gatheredDetails,
                $extraHoldFields,
                $requestGroups
            );
            $validPickup = $validGroup && $this->holds()->validatePickUpInput(
                $gatheredDetails['pickUpLocation'] ?? null,
                $extraHoldFields,
                $pickup
            );
            $dateValidationResults = $this->holds()->validateDates(
                $gatheredDetails['startDate'] ?? null,
                $gatheredDetails['requiredBy'] ?? null,
                $extraHoldFields
            );
            if (!$validGroup) {
                $this->flashMessenger()
                    ->addErrorMessage('hold_invalid_request_group');
            }
            if (!$validPickup) {
                $this->flashMessenger()->addErrorMessage('hold_invalid_pickup');
            }
            foreach ($dateValidationResults['errors'] as $msg) {
                $this->flashMessenger()->addErrorMessage($msg);
            }
            if ($validGroup && $validPickup && !$dateValidationResults['errors']) {
                // If we made it this far, we're ready to place the hold;
                // if successful, we will redirect and can stop here.

                // Pass start date to the driver only if it's in the future:
                if (!empty($gatheredDetails['startDate'])
                    && $dateValidationResults['startDateTS'] < strtotime('+1 day')
                ) {
                    $gatheredDetails['startDate'] = '';
                    $dateValidationResults['startDateTS'] = 0;
                }

                // Add patron data and converted dates to submitted data
                $holdDetails = $gatheredDetails + [
                        'patron' => $patron,
                        'startDateTS' => $dateValidationResults['startDateTS'],
                        'requiredByTS' => $dateValidationResults['requiredByTS'],
                    ];

                // Attempt to place the hold:
                $function = (string)$checkHolds['function'];
                $results = $catalog->$function($holdDetails);

                // Success: Go to Display Holds
                if (isset($results['success']) && $results['success'] == true) {
                    $msg = [
                        'html' => true,
                        'msg' => 'hold_place_success_html',
                        'tokens' => [
                            '%%url%%' => $this->url()->fromRoute('holds-list')
                        ],
                    ];
                    $this->flashMessenger()->addMessage($msg, 'success');
                    if (!empty($results['warningMessage'])) {
                        $this->flashMessenger()
                            ->addWarningMessage($results['warningMessage']);
                    }
                    return $this->redirectToRecord('#top');
                } else {
                    // Failure: use flash messenger to display messages, stay on
                    // the current form.
                    if (isset($results['status'])) {
                        $this->flashMessenger()
                            ->addMessage($results['status'], 'error');
                    }
                    if (isset($results['sysMessage'])) {
                        $this->flashMessenger()
                            ->addMessage($results['sysMessage'], 'error');
                    }
                }
            }
        }

        // Set default start date to today:
        $dateConverter = $this->serviceLocator->get(\VuFind\Date\Converter::class);
        $defaultStartDate = $dateConverter->convertToDisplayDate('U', time());

        // Find and format the default required date:
        $defaultRequiredDate = $dateConverter->convertToDisplayDate(
            'U',
            $this->holds()->getDefaultRequiredDate(
                $checkHolds,
                $catalog,
                $patron,
                $gatheredDetails
            )
        );
        try {
            $defaultPickup
                = $catalog->getDefaultPickUpLocation($patron, $gatheredDetails);
        } catch (\Exception $e) {
            $defaultPickup = false;
        }
        try {
            $defaultRequestGroup = empty($requestGroups)
                ? false
                : $catalog->getDefaultRequestGroup($patron, $gatheredDetails);
        } catch (\Exception $e) {
            $defaultRequestGroup = false;
        }

        $config = $this->getConfig();
        $homeLibrary = ($config->Account->set_home_library ?? true)
            ? $this->getUser()->home_library : '';
        // helpText is only for backward compatibility:
        $helpText = $helpTextHtml = $checkHolds['helpText'];

        $view = $this->createViewModel(
            compact(
                'gatheredDetails',
                'pickup',
                'defaultPickup',
                'homeLibrary',
                'extraHoldFields',
                'defaultStartDate',
                'defaultRequiredDate',
                'requestGroups',
                'defaultRequestGroup',
                'requestGroupNeeded',
                'helpText',
                'helpTextHtml',
                'orderInQueue'
            )
        );
        $view->setTemplate('record/hold');
        return $view;
    }
}
