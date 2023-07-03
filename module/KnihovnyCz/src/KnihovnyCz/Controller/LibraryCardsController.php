<?php

/**
 * Class LibraryCardsController
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

use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use VuFind\Controller\LibraryCardsController as LibraryCardsControllerBase;

/**
 * Class LibraryCardsController
 *
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class LibraryCardsController extends LibraryCardsControllerBase
{
    /**
     * Send user's library cards to the view
     *
     * @return mixed
     */
    public function homeAction()
    {
        $model = parent::homeAction();
        if ($model instanceof ViewModel) {
            $model->setVariable(
                'csrfHash',
                $this->getAuthManager()->getCsrfHash(false)
            );
        }
        return $model;
    }

    /**
     * Creates a confirmation box to delete or not delete the current list
     *
     * @return mixed
     */
    public function deleteCardAction()
    {
        try {
            return parent::deleteCardAction();
        } catch (\Exception $ex) {
            // Display error message instead of error page
            $this->flashMessenger()->addMessage($ex->getMessage(), 'error');
            // Redirect to MyResearch library cards
            return $this->redirect()->toRoute('librarycards-home');
        }
    }

    /**
     * Not supported action.
     *
     * @return \Laminas\Http\Response
     */
    public function notSupportedAction(): \Laminas\Http\Response
    {
        $this->flashMessenger()->addErrorMessage(
            "Library cards are not supported in this view"
        );
        return $this->redirect()->toRoute('myresearch-home');
    }

    /**
     * Check if library cards are enabled.
     *
     * @param \Laminas\Mvc\MvcEvent $e Event
     *
     * @return void
     */
    public function validateLibraryCardsEnabled(MvcEvent $e): void
    {
        $notSupportedRoute = 'librarycards-notsupported';
        $route = $e->getRouteMatch()->getMatchedRouteName();
        if ($route == $notSupportedRoute) {
            return;
        }
        if (!($user = $this->getUser())) {
            return;
        }
        if (
            !$user->libraryCardsEnabled()
            || $user->isSingleCard() || $user->hasLibraryCardsFilter()
        ) {
            // flash messenger does not work in dispatch, so redirect
            // to separate action
            $e->setResponse($this->redirect()->toRoute($notSupportedRoute));
        }
    }

    /**
     * Register the default events for this controller
     *
     * @return void
     */
    protected function attachDefaultListeners(): void
    {
        parent::attachDefaultListeners();
        $events = $this->getEventManager();
        $events->attach(
            MvcEvent::EVENT_DISPATCH,
            [$this, 'validateLibraryCardsEnabled'],
            1000
        );
    }

    /**
     * Process the "edit library card" submission. Only update card name.
     *
     * @param \VuFind\Db\Row\User $user Logged in user
     *
     * @return object|false        Response object if redirect is
     * needed, false if form needs to be redisplayed.
     */
    protected function processEditLibraryCard($user)
    {
        $this->flashMessenger()->addErrorMessage(
            "Editation of library cards is not supported"
        );
        return $this->redirect()->toRoute('librarycards-home');
    }
}
