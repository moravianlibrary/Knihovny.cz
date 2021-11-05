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

use KnihovnyCz\Session\NullSessionManager;
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

    /**
     * Delete user account if it is confirmed
     *
     * @return mixed|\Laminas\Http\Response
     */
    public function deleteUserAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!$user = $this->getAuthManager()->isLoggedIn()) {
            $this->flashExceptions($this->flashMessenger());
            return $this->forceLogin();
        }

        $confirm = $this->params()->fromPost('confirm', false);
        $csrf = $this->params()->fromPost('csrf', null);

        if ($confirm && $this->getAuthManager()->isValidCsrfHash($csrf)) {
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
            $this->disableSession();
            $view = parent::finesAction();
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
            $this->disableSession();
            $view = parent::profileAction();
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
        try {
            $this->disableSession();
            $view = parent::checkedoutAction();
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
        $view->setTemplate('myresearch/checkedout-ajax');
        $view->cardId = $this->getCardId();
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
            $this->disableSession();
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
        $view->cardId = $this->getCardId();
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
    }

    /**
     * Does the user have catalog credentials available?  Returns associative array
     * of patron data if so, otherwise forwards to appropriate login prompt and
     * returns false. If there is an ILS exception, a flash message is added and
     * a newly created ViewModel is returned.
     *
     * @return bool|array|ViewModel
     */
    protected function catalogLogin()
    {
        $user = $this->getAuthManager()->isLoggedIn();
        if ($user == false) {
            return $this->forceLogin();
        }
        $cardId = $this->getCardId();
        if ($cardId != null) {
            $card = $user->getLibraryCard($cardId);
            if ($card != null) {
                $user->cat_username = $card->cat_username;
                $user->cat_password = $card->cat_password;
            }
        }
        $catalog = $this->getILS();
        $patron = $catalog->patronLogin(
            $user->cat_username,
            $user->getCatPassword()
        );
        return $patron;
    }

    /**
     * Return card id to use
     *
     * @return string|null
     */
    protected function getCardId()
    {
        return $this->getRequest()->getQuery(
            'cardId',
            $this->getRequest()->getPost('cardId')
        );
    }

    /**
     * Disable session use in flash manager.
     *
     * @return void
     */
    protected function disableSession()
    {
        $this->flashMessenger()->setSessionManager(new NullSessionManager());
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
        parent::processAuthenticationException();
    }
}
