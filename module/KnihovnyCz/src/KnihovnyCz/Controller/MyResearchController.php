<?php

/**
 * Class MyResearchController
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
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
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\Controller;

use KnihovnyCz\Db\Table\UserListCategories;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Stdlib\ResponseInterface as Response;
use Laminas\View\Model\ViewModel;
use VuFind\Controller\MyResearchController as MyResearchControllerBase;
use VuFind\Db\Table\PluginManager as TableManager;
use VuFind\Db\Table\UserList;
use VuFind\Exception\Auth as AuthException;
use VuFind\Exception\Forbidden as ForbiddenException;
use VuFind\Validator\CsrfInterface;

use function is_array;

/**
 * Class MyResearchController
 *
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 *
 * @method Plugin\FlashRedirect flashRedirect() Flash redirect controller plugin
 * @method Plugin\ShortLoans shortLoans() Time slots controller plugin
 */
class MyResearchController extends MyResearchControllerBase
{
    use \VuFind\Controller\AjaxResponseTrait;
    use \KnihovnyCz\Controller\CatalogLoginTrait;

    use \KnihovnyCz\Controller\MyResearchTrait;

    /**
     * Date converter object
     *
     * @var \KnihovnyCz\Date\Converter
     */
    protected $dateConverter = null;

    /**
     * Feedback form class
     *
     * @var string
     */
    protected $illFormClass = \KnihovnyCz\Form\IllForm::class;

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service locator
     */
    public function __construct(ServiceLocatorInterface $sm)
    {
        parent::__construct($sm);
        $this->dateConverter = $sm->get(\KnihovnyCz\Date\Converter::class);
    }

    /**
     * Delete user account if it is confirmed
     *
     * @return mixed|\Laminas\Http\Response
     */
    public function deleteUserAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!$user = $this->getAuthManager()->isLoggedIn()) {
            return $this->forceLogin();
        }

        $confirm = $this->params()->fromPost('confirm', false);
        $csrf = $this->params()->fromPost('csrf', null);

        /**
         * Auth manager
         *
         * @var \KnihovnyCz\Auth\Manager $authManager
         */
        $authManager = $this->getAuthManager();
        if ($confirm && $authManager->isValidCsrfHash($csrf)) {
            $user->delete();
            return $this->logoutAction();
        }

        $this->flashMessenger()->addErrorMessage(
            $this->translate('delete_user_account_not_confirmed')
        );
        return $this->redirect()->toRoute('librarycards-home');
    }

    /**
     * Send list of fines to view
     *
     * @return mixed
     */
    public function finesAction()
    {
        // Force login:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }
        $view = $this->createViewModel();
        $view->setTemplate('myresearch/fines-all');
        return $view;
    }

    /**
     * Send list of fines to view as HTML for rendering in AJAX
     *
     * @return mixed
     */
    public function finesAjaxAction()
    {
        try {
            $this->flashRedirect()->restore();
            $view = parent::finesAction();
        } catch (\Exception $ex) {
            $view = $this->createViewModel();
            $this->showException($ex);
        }
        if ($view instanceof ViewModel) {
            // Check function config
            $catalog = $this->getILS();
            $patron = $this->catalogLogin();
            $functionConfig = $catalog->checkFunction(
                'getMyPaymentLink',
                $patron
            );
            if ($functionConfig !== false && !empty($view->fines)) {
                $totalDue = 0;
                foreach ($view->fines as $fine) {
                    $totalDue += $fine['balance'] ?? 0;
                }
                if ($totalDue < 0) {
                    $view->paymentLink = $catalog
                        ->getMyPaymentLink($patron, -1 * $totalDue);
                }
            }
        } else {
            $view = $this->createViewModel(
                [
                    'error' => 'ils_offline_home_message',
                ]
            );
        }
        $view->setTemplate('myresearch/fines-ajax');
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
    }

    /**
     * Gather user profile data
     *
     * @return mixed
     */
    public function profileAction()
    {
        // Force login:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }
        $view = $this->createViewModel();
        $view->setTemplate('myresearch/profile-all');
        return $view;
    }

    /**
     * Send user profile data as HTML for rendering in AJAX
     *
     * @return mixed
     */
    public function profileAjaxAction()
    {
        try {
            $this->flashRedirect()->restore();
            $view = parent::profileAction();
        } catch (\Exception $ex) {
            $view = $this->createViewModel();
            $view->error = true;
            $this->showException($ex);
        }
        if ($view instanceof \Laminas\View\Model\ViewModel) {
            if (
                isset($view->profile)
                && isset($view->profile['expired'])
                && $view->profile['expired']
            ) {
                $this->flashMessenger()->addErrorMessage(
                    'library_card_expirated_warning'
                );
            }
            $catalog = $this->getILS();
            $patron = $this->catalogLogin();
            $functionConfig = $catalog->checkFunction(
                'getMyProlongRegistrationLink',
                $patron
            );
            if ($functionConfig !== false) {
                $view->prolongRegistrationLink = $catalog
                    ->getMyProlongRegistrationLink($patron);
            }
        } else {
            $view = $this->createViewModel(
                [
                    'error' => 'ils_offline_home_message',
                ]
            );
        }
        $view->setTemplate('myresearch/profile-ajax');
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
    }

    /**
     * Send list of checked out books to view
     *
     * @return mixed
     */
    public function checkedoutAction()
    {
        // Force login:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }
        $view = $this->createViewModel();
        $view->setTemplate('myresearch/checkedout-all');
        return $view;
    }

    /**
     * Send user profile data as HTML for rendering in AJAX
     *
     * @return mixed
     */
    public function checkedoutAjaxAction()
    {
        $this->flashRedirect()->restore();
        $view = null;
        try {
            $view = parent::checkedoutAction();
        } catch (\Exception $ex) {
            $this->showException($ex);
        }
        $error = ($view == null || !($view instanceof ViewModel));
        if (!$error) {
            $transactions = $view->transactions ?? [];
            $this->addDetailsFromOfflineHoldings($transactions);
        }
        // active operation failed -> redirect to show checked out items
        if ($this->getRequest()->isPost() && $error) {
            $url = $this->url()->fromRoute('myresearch-checkedoutajax');
            return $this->flashRedirect()->toUrl(
                $url . '?cardId='
                . $this->getCardId()
            );
        }
        if ($view == null) {
            $view = new ViewModel();
            $view->error = $error;
        }
        // disable sorting
        $view->sortList = false;
        $view->setTemplate('myresearch/checkedout-ajax');
        $params = $view->getVariable('params', []);
        $params['cardId'] = $this->getCardId();
        $view->setVariable('params', $params);
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
    }

    /**
     * Logout Action
     *
     * @return mixed
     */
    public function logoutAction()
    {
        $account = $this->getAccountContainer();
        $logout = $account->userInfo['safeLogout'] ?? 'global';
        $config = $this->getConfig();
        $logoutTarget = '';
        if ($logout != 'global') {
            $logoutTarget = $this->getServerUrl('myresearch-logoutwarning');
        } elseif (!empty($config->Site->logOutRoute)) {
            $logoutTarget = $this->getServerUrl($config->Site->logOutRoute);
        } else {
            $logoutTarget = $this->getRequest()->getServer()->get('HTTP_REFERER');
            if (empty($logoutTarget)) {
                $logoutTarget = $this->getServerUrl('home');
            }

            // If there is an auth_method parameter in the query, we should strip
            // it out. Otherwise, the user may get stuck in an infinite loop of
            // logging out and getting logged back in when using environment-based
            // authentication methods like Shibboleth.
            $logoutTarget = preg_replace(
                '/([?&])auth_method=[^&]*&?/',
                '$1',
                $logoutTarget
            );
            $logoutTarget = rtrim($logoutTarget, '?');

            // Another special case: if logging out will send the user back to
            // the MyResearch home action, instead send them all the way to
            // VuFind home. Otherwise, they might get logged back in again,
            // which is confusing. Even in the best scenario, they'll just end
            // up on a login screen, which is not helpful.
            if ($logoutTarget == $this->getServerUrl('myresearch-home')) {
                $logoutTarget = $this->getServerUrl('home');
            }
        }

        /**
         * Auth manager
         *
         * @var \KnihovnyCz\Auth\Manager
         */
        $authManager = $this->getAuthManager();
        $extLogout = ($logout != 'none');
        return $this->redirect()
            ->toUrl($authManager->logout($logoutTarget, true, $extLogout));
    }

    /**
     * Logout Warning Action
     *
     * @return mixed
     */
    public function logoutWarningAction()
    {
        if ($this->getUser()) {
            return $this->redirect()
                ->toUrl($this->getServerUrl('myresearch-home'));
        }
        return $this->createViewModel();
    }

    /**
     * User settings action
     *
     * @return \Laminas\View\Model\ViewModel|mixed
     */
    public function userSettingsAction()
    {
        // Force login:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }

        // Fail if user settings are disabled.
        $check = $this->serviceLocator
            ->get(\KnihovnyCz\Config\AccountCapabilities::class);
        if (!$check->isUserSettingsEnabled()) {
            throw new ForbiddenException('User settings disabled.');
        }

        /**
         * User setting service
         *
         * @var \KnihovnyCz\Service\UserSettingsService
         */
        $userSettings = $this->serviceLocator
            ->get(\KnihovnyCz\Service\UserSettingsService::class);
        if ($this->params()->fromPost('submit')) {
            if ($userSettings->save($this->params()->fromPost())) {
                $this->flashMessenger()->addSuccessMessage(
                    $this->translate('user_settings_updated')
                );
            } else {
                $this->flashMessenger()->addErrorMessage(
                    $this->translate('user_settings_update_failed')
                );
            }
        }
        $view = $this->createViewModel();
        $settings = $this->serviceLocator
            ->get(\KnihovnyCz\Service\UserSettingsService::class)
            ->getAvailableSettings();
        $view->recordsPerPage = $settings['recordsPerPage'];
        $view->citationStyle = $settings['citationStyle'];
        $view->setTemplate('myresearch/usersettings');
        return $view;
    }

    /**
     * Short loans action
     *
     * @return \Laminas\View\Model\ViewModel|mixed
     */
    public function shortLoansAction()
    {
        // Force login:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }
        $view = $this->createViewModel();
        $view->setTemplate('myresearch/shortloans-all');
        return $view;
    }

    /**
     * Send list of fines to view as HTML for rendering in AJAX
     *
     * @return mixed
     */
    public function shortLoansAjaxAction()
    {
        $this->flashRedirect()->restore();
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        $this->shortLoans()->cancelShortLoans(
            $patron,
            $this->serviceLocator->get(CsrfInterface::class)
        );

        $view = $this->createViewModel();

        // Connect to the ILS:
        $catalog = $this->getILS();

        // Check function config
        $functionConfig = $catalog->checkFunction(
            'getMyShortLoans',
            $patron
        );

        $view->cancelForm = $functionConfig;
        if (false === $functionConfig) {
            $this->flashMessenger()->addErrorMessage('ils_action_unavailable');
        } else {
            $requests = $catalog->getMyShortLoans($patron);
            $this->shortLoans()->addCancelDetails($requests);
            $requests = $this->ilsRecords()->getDrivers($requests);
            $view->recordList = $requests;
            $view->links = $catalog->getMyShortLoanLinks($patron);
            if (empty($requests)) {
                $this->flashMessenger()->addInfoMessage('short_loan_empty_list');
            }
        }
        $view->setTemplate('myresearch/shortloans-ajax');
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
    }

    /**
     * Action for finished payment
     *
     * @return mixed
     */
    public function finesPaymentAction()
    {
        $status = $this->getRequest()->getQuery('status');
        switch ($status) {
            case 'ok':
                $this->flashMessenger()
                    ->addInfoMessage('online_payment_fine_proceed_ok');
                break;
            case 'nok':
                $this->flashMessenger()
                    ->addErrorMessage('online_payment_fine_proceed_nok');
                break;
            case 'error':
            default:
                $this->flashMessenger()
                    ->addErrorMessage('online_payment_fine_proceed_error');
                break;
        }
        return $this->redirect()->toRoute('myresearch-fines');
    }

    /**
     * Action for finished payment of registration prolongation
     *
     * @return mixed
     */
    public function prolongationPaymentAction()
    {
        $status = $this->params()->fromQuery('status');
        switch ($status) {
            case 'ok':
                $this->flashMessenger()
                    ->addInfoMessage('online_prolongation_payment_ok');
                break;
            case 'nok':
                $this->flashMessenger()
                    ->addErrorMessage('online_prolongation_payment_nok');
                break;
            case 'error':
            default:
                $this->flashMessenger()
                    ->addErrorMessage('online_prolongation_payment_error');
                break;
        }
        return $this->redirect()->toRoute('myresearch-profile');
    }

    /**
     * Send user's saved favorites from a particular list to the view
     *
     * @return mixed
     */
    public function mylistAction()
    {
        $user = $this->getUser();
        $tables = $this->serviceLocator->get(TableManager::class);
        $category = $this->params()->fromPost('category', false);
        $listId = $this->params()->fromRoute('id');
        if ($category && $user && $user->couldManageInspirationLists()) {
            $listTable = $tables->get(UserList::class);
            $listTable->update(
                ['category' => $category],
                ['id' => $listId]
            );
        }
        $parentView = parent::mylistAction();
        if ($parentView instanceof \Laminas\View\Model\ViewModel) {
            $categoriesTable = $tables->get(UserListCategories::class);
            $categories = $categoriesTable->select();
            $parentView->setVariable('categories', $categories);
            if ($user && $user->couldManageInspirationLists()) {
                $blockManager = $this->serviceLocator
                    ->get(\VuFind\ContentBlock\PluginManager::class);
                $contentBlock = $blockManager->get('UserList');
                $contentBlock->setConfig($listId . ':0');
                $parentView->setVariable('listBlock', $contentBlock->getContext());
            }
        }
        return $parentView;
    }

    /**
     * Front page login action
     *
     * @return \Laminas\Http\Response
     */
    public function frontPageLoginAction(): \Laminas\Http\Response
    {
        if (!$this->getUser()) {
            $urlHelper = $this->serviceLocator->get('ViewHelperManager')->get('url');
            $query = ['lbreferer' => $urlHelper('myresearch-home')];
            return $this->redirect()->toRoute('myresearch-userlogin', [], ['query' => $query]);
        }
        return $this->redirect()->toRoute('myresearch-home');
    }

    /**
     * Send list of ill requests to view
     *
     * @return mixed
     */
    public function illRequestsAction(): ViewModel|Response
    {
        // Force login:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }
        $view = $this->createViewModel();
        $new = $this->getRequest()->getQuery('new');
        if ($new == null) {
            $view->setTemplate('myresearch/illrequests-all');
            return $view;
        }
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }
        $hasForms = $this->getILS()->checkFunction(
            'getBlankIllRequestTypes',
            compact('patron')
        );
        if (!$hasForms) {
            $this->flashMessenger()->addErrorMessage(
                $this->translate('ils_action_unavailable')
            );
            return $this->redirect()->toRoute('myresearch-illrequests');
        }
        $view->type = $new;
        $view->setTemplate('myresearch/illrequests-new');
        $catalog = $this->getILS();
        $formFields = $catalog->getFormForBlankILLRequest($patron, $new);
        if ($formFields == null) {
            $this->flashMessenger()->addErrorMessage(
                $this->translate('ill_blank_unknown_request_type')
            );
            return $this->redirect()->toRoute('myresearch-illrequests');
        }
        $form = $this->serviceLocator->get($this->illFormClass);
        $form->init($formFields);
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost()->toArray();
            $form->setData($data);
            if ($form->isValid()) {
                $data['type'] = $new;
                $result = $catalog->placeBlankILLRequest($patron, $data);
                if ($result['success'] == false) {
                    $this->flashMessenger()->addErrorMessage('ill_blank_request_failed');
                } else {
                    $this->flashMessenger()->addInfoMessage('ill_blank_request_created');
                    return $this->redirect()->toRoute('myresearch-illrequests');
                }
            } else {
                $this->flashMessenger()->addErrorMessage('ill_blank_request_form_validation_error');
            }
        }
        $view->form = $form;
        $view->card = $user->getLibraryCard((int)$this->getCardId());
        $view->groups = $formFields;
        return $view;
    }

    /**
     * Send list of ILL requests to view as HTML for rendering in AJAX
     *
     * @return mixed
     */
    public function illRequestsAjaxAction(): ViewModel|Response
    {
        $view = null;
        try {
            if (!is_array($patron = $this->catalogLogin())) {
                return $patron;
            }
            $this->flashRedirect()->restore();
            $catalog = $this->getILS();
            $functionConfig = $catalog->checkFunction('ILLRequests', $patron);
            if ($functionConfig !== false) {
                $view = parent::illRequestsAction();
                $forms = [];
                $hasForms = $catalog->checkFunction(
                    'getBlankIllRequestTypes',
                    compact('patron')
                );
                if ($hasForms) {
                    $result = $catalog->getBlankIllRequestTypes($patron);
                    foreach ($result as $name) {
                        $options = [
                            'query' => [
                                'new' => $name,
                                'cardId' => $this->getCardId(),
                            ],
                        ];
                        $label = 'ill_blank_new_request_for_' . $name;
                        $forms[$label] = $this->url()->fromRoute('myresearch-illrequests', [], $options);
                    }
                }
                $view->setVariable('forms', $forms);
            } else {
                $this->flashMessenger()->addErrorMessage('ils_action_unavailable');
            }
        } catch (\Exception $ex) {
            $this->showException($ex);
        }

        if (!($view instanceof \Laminas\View\Model\ViewModel)) {
            $view = $this->createViewModel(
                [
                    'error' => 'ils_offline_home_message',
                ]
            );
        }

        if ($this->flashMessenger()->hasCurrentMessages('error')) {
            $view->error = true;
        }
        $view->setTemplate('myresearch/illrequests-ajax');
        $view->setVariable('cardId', $this->getCardId());
        $params = $view->getVariable('params', []);
        $params['cardId'] = $this->getCardId();
        $view->setVariable('params', $params);
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
    }

    /**
     * Create new ILL request
     *
     * @return mixed
     */
    public function illRequestNewAction(): ViewModel|Response
    {
        $view = $this->createViewModel();
        $form = $this->serviceLocator->get($this->illFormClass);
        $view->form = $form;
        return $view;
    }

    /**
     * Direct login action.
     *
     * @return mixed
     */
    public function directloginAction(): ViewModel|Response
    {
        // Don't log in if already logged in!
        if ($this->getAuthManager()->isLoggedIn()) {
            return $this->redirect()->toRoute('home');
        }
        if ($si = $this->getSessionInitiator()) {
            return $this->redirect()->toUrl($si);
        }
        return $this->forwardTo('MyResearch', 'Login');
    }

    /**
     * Process an authentication error.
     *
     * @param AuthException $e Exception to process.
     *
     * @return void
     */
    protected function processAuthenticationException(AuthException $e)
    {
        if ($e->getMessage() == 'Missing configuration for IdP.') {
            $this->flashMessenger()->addMessage(
                'You must be logged in first',
                'error'
            );
            return;
        }
        parent::processAuthenticationException($e);
    }

    /**
     * Return a session container with user account.
     *
     * @return \Laminas\Session\Container
     */
    protected function getAccountContainer()
    {
        return new \Laminas\Session\Container(
            'Account',
            $this->serviceLocator->get(\Laminas\Session\SessionManager::class)
        );
    }
}
