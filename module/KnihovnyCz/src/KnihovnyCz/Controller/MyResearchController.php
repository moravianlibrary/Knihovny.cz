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
            $view = $this->createViewModel(
                [
                'error' => 'ils_offline_home_message'
                ]
            );
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
            $view = $this->createViewModel(
                [
                'error' => 'ils_offline_home_message'
                ]
            );
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
     * Does the user have catalog credentials available?  Returns associative array
     * of patron data if so, otherwise forwards to appropriate login prompt and
     * returns false. If there is an ILS exception, a flash message is added and
     * a newly created ViewModel is returned.
     *
     * @return bool|array|ViewModel
     */
    protected function catalogLogin()
    {
        $patron = parent::catalogLogin();
        $cardId = $this->getRequest()->getQuery('cardId');
        if (is_array($patron) && $cardId != null) {
            $card = $this->getAuthManager()->isLoggedIn()->getLibraryCard($cardId);
            if ($card != null) {
                $patron['id'] = $card['cat_username'];
                $patron['cat_username'] = $card['cat_username'];
                $patron['cat_password'] = $card['cat_password'];
            }
        }
        return $patron;
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
}
