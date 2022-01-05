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

use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;
use VuFind\Controller\MyResearchController as MyResearchControllerBase;
use VuFind\Exception\Auth as AuthException;

/**
 * Class MyResearchController
 *
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class MyResearchController extends MyResearchControllerBase
{
    use \VuFind\Controller\AjaxResponseTrait;

    use \KnihovnyCz\Controller\CatalogLoginTrait;

    /**
     * Date converter object
     *
     * @var \KnihovnyCz\Date\Converter
     */
    protected $dateConverter = null;

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
            $this->flashMessenger()->addErrorMessage($ex->getMessage());
        }
        if (!($view instanceof ViewModel)) {
            $view = $this->createViewModel(
                [
                'error' => 'ils_offline_home_message'
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
            $this->flashMessenger()->addErrorMessage($ex->getMessage());
        }
        if ($view instanceof \Laminas\View\Model\ViewModel) {
            if (isset($view->profile)
                && isset($view->profile['expiration_date'])
                && $this->isExpired($view->profile['expiration_date'])
            ) {
                $this->flashMessenger()->addErrorMessage(
                    'library_card_expirated_warning'
                );
            }
        } else {
            $view = $this->createViewModel(
                [
                    'error' => 'ils_offline_home_message'
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
            $this->flashMessenger()->addErrorMessage($ex->getMessage());
        }
        $error = ($view == null || !($view instanceof ViewModel));
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
        } elseif (isset($view->transactions)) {
            foreach ($view->transactions as $resource) {
                $ilsDetails = $resource->getExtraDetail('ils_details');
                if (isset($ilsDetails['duedate'])
                    && $this->isExpired($ilsDetails['duedate'])
                ) {
                    $ilsDetails['dueStatus'] = 'overdue';
                    $resource->setExtraDetail('ils_details', $ilsDetails);
                }
            }
        }
        // disable sorting
        $view->sortList = false;
        $view->cardId = $this->getCardId();
        $view->setTemplate('myresearch/checkedout-ajax');
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
    }

    /**
     * Send list of historic loans to view
     *
     * @return mixed
     */
    public function historicloansAction()
    {
        // Force login:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }
        $view = $this->createViewModel();
        $view->setTemplate('myresearch/historicloans-all');
        return $view;
    }

    /**
     * Send list of historic loans to view
     *
     * @return mixed
     */
    public function historicloansAjaxAction()
    {
        try {
            $this->flashRedirect()->restore();
            $view = parent::historicloansAction();
            // disable sorting
            $view->sortList = false;
        } catch (\Exception $ex) {
            $view = $this->createViewModel();
            $this->flashMessenger()->addErrorMessage($ex->getMessage());
        }
        if (!($view instanceof \Laminas\View\Model\ViewModel)) {
            $view = $this->createViewModel(
                [
                    'error' => 'ils_offline_home_message'
                ]
            );
        }
        $view->setTemplate('myresearch/historicloans-ajax');
        $view->setVariable('cardId', $this->getCardId());
        $params = $view->getVariable('params', []);
        $params['cardId'] = $this->getCardId();
        $view->setVariable('params', $params);
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
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
     * Return if the date is in the past, used for checking expired checked
     * out items or registrations.
     *
     * @param string $date Expiration date
     *
     * @return bool is expired
     */
    protected function isExpired(string $date): bool
    {
        if ($expire = $this->dateConverter->parseDisplayDate($date)) {
            $dateDiff = $expire->diff(new \DateTime());
            return $dateDiff->invert == 0 && $dateDiff->days > 0;
        }
        return false;
    }
}
