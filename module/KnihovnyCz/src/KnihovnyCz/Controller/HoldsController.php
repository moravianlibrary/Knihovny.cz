<?php

/**
 * Class HoldsController
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
use VuFind\Controller\HoldsController as HoldsControllerBase;

/**
 * Class HoldsController
 *
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class HoldsController extends HoldsControllerBase
{
    use \VuFind\Controller\AjaxResponseTrait;

    /**
     * Send list of holds to view
     *
     * @return mixed
     */
    public function listAction()
    {
        // Force login:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }
        $view = $this->createViewModel();
        $view->setTemplate('holds/list-all');
        return $view;
    }

    /**
     * Send list of holds to view
     *
     * @return mixed
     */
    public function listAjaxAction()
    {
        try {
            $this->disableSession();
            $view = parent::listAction();
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
        $view->setTemplate('holds/list-ajax');
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
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
