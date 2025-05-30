<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\Config\Config;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Stdlib\ResponseInterface as Response;
use VuFind\Validator\CsrfInterface;

/**
 * Class RecordController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 *
 * @method \Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger  flashMessenger
 * @method Plugin\ShortLoans shortLoans() Time slots controller plugin
 */
class RecordController extends \VuFind\Controller\RecordController
{
    use ZiskejMvsTrait;
    use ZiskejEddTrait;

    /**
     * Redirect library records to library record controller?
     *
     * @var boolean
     */
    protected $redirectToLibrary = false;

    /**
     * Source to redirect to when no source is present
     *
     * @var string
     */
    protected string $defaultSource;

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
            == 'Libraries directory';
        $this->defaultSource = ($config->Record->defaultSource ?? '');
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
        if (
            $this->redirectToLibrary
            && str_starts_with($this->params()->fromRoute('id'), 'library')
        ) {
            return $this->redirect()->toRoute(
                'search2record',
                $this->params()->fromRoute()
            );
        }
        $id = $this->params()->fromRoute('id');
        if (
            !empty($this->defaultSource)
            && count(explode('.', $id)) === 1
        ) {
            $routeParams = $this->params()->fromRoute();
            $routeParams['id'] = $this->defaultSource . '.' . $id;
            return $this->redirect()->toRoute(
                null,
                $routeParams,
                ['query' => $this->params()->fromQuery()]
            );
        }
        return parent::dispatch($request, $response);
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
                'patron' => $patron,
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
                'patron' => $patron,
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
            ? explode(':', $checkHolds['extraHoldFields']) : [];

        $requestGroupNeeded = in_array('requestGroup', $extraHoldFields)
            && !empty($requestGroups)
            && (empty($gatheredDetails['level'])
                || ($gatheredDetails['level'] != 'copy'
                    || count($requestGroups) > 1));

        $pickupDetails = $gatheredDetails;
        if (
            !$requestGroupNeeded && !empty($requestGroups)
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
                if (
                    !empty($gatheredDetails['startDate'])
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
                            '%%url%%' => $this->url()->fromRoute('holds-list'),
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

        $linkText = $this->params()->fromQuery('linkText');

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
                'orderInQueue',
                'linkText'
            )
        );
        $view->setTemplate('record/hold');
        return $view;
    }

    /**
     * Share record action
     *
     * @return \Laminas\View\Model\ViewModel
     */
    public function shareAction()
    {
        $view = $this->createViewModel();
        $view->setTemplate('record/share');
        return $view;
    }

    /**
     * Display a particular tab.
     *
     * @param string $tab  Name of tab to display
     * @param bool   $ajax Are we in AJAX mode?
     *
     * @return mixed
     */
    protected function showTab($tab, $ajax = false)
    {
        $isLogin = $this->params()->fromQuery('login', 'false') == 'true';
        if ($isLogin) {
            // already logged - redirect to new URL without login parameter
            if ($this->getUser()) {
                $serverUrl = $this->getServerUrl();
                $serverUrl = str_replace(
                    [ '?login=true', '&login=true'],
                    [ '?', '&'],
                    $serverUrl
                );
                return $this->redirect()->toUrl($serverUrl);
            } else {
                return $this->forceLogin(null, [], false);
            }
        }
        return parent::showTab($tab, $ajax);
    }

    /**
     * Redirect the user to the login screen.
     *
     * @param string $msg     Flash message to display on login screen
     * @param array  $extras  Associative array of extra fields to store
     * @param bool   $forward True to forward, false to redirect
     *
     * @return mixed
     */
    public function forceLogin($msg = null, $extras = [], $forward = true)
    {
        // Set default message if necessary.
        if (null === $msg) {
            $msg = 'You must be logged in first';
        }

        // We don't want to return to the lightbox
        $serverUrl = $this->getServerUrl();
        $serverUrl = str_replace(
            ['?layout=lightbox', '&layout=lightbox'],
            ['?', '&'],
            $serverUrl
        );

        // Store the current URL as a login followup action
        $this->followup()->store($extras, $serverUrl);
        if (!empty($msg) && $forward) {
            $this->flashMessenger()->addMessage($msg, 'error');
        }

        // Set a flag indicating that we are forcing login:
        $this->getRequest()->getPost()->set('forcingLogin', true);

        if ($forward) {
            return $this->forwardTo('MyResearch', 'Login');
        }
        return $this->redirect()->toRoute('myresearch-directlogin');
    }

    /**
     * Redirect the user to the main record view.
     *
     * @param string $params Parameters to append to record URL.
     * @param string $tab    Record tab to display (null for default).
     *
     * @return mixed
     */
    protected function redirectToRecord($params = '', $tab = null)
    {
        $details = $this->getRecordRouter()
            ->getTabRouteDetails($this->loadRecord(), $tab);
        $target = $this->url()->fromRoute($details['route'], $details['params']);
        $sid = $this->params()->fromQuery('sid');
        if ($sid !== null) {
            [$query, $fragment] = explode('#', $params, 2);
            $query .= str_starts_with($query, '?') ? '&' : '?';
            $query .= 'sid=' . urlencode($sid);
            $params = $query . '#' . $fragment;
        }
        return $this->redirect()->toUrl($target . $params);
    }
}
