<?php

/**
 * Class HoldsController
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
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

use Laminas\View\Model\ViewModel;
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

    use \KnihovnyCz\Controller\CatalogLoginTrait;

    use \KnihovnyCz\Controller\MyResearchTrait;

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
        $this->warnSocialUser();
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
        $this->flashRedirect()->restore();
        $view = null;
        try {
            $view = parent::listAction();
        } catch (\Exception $ex) {
            $this->showException($ex);
        }
        $error = ($view == null || !($view instanceof ViewModel));
        // active operation failed -> redirect to show checked out items
        if ($this->getRequest()->isPost() && $error) {
            $url = $this->url()->fromRoute('holds-listajax');
            return $this->flashRedirect()->toUrl(
                $url . '?cardId='
                . $this->getCardId()
            );
        }
        if ($view == null) {
            $view = new ViewModel();
            $view->error = $error;
        }
        $view->setTemplate('holds/list-ajax');
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
    }
}
