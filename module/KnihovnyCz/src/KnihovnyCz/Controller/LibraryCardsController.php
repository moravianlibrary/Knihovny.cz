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

use VuFind\Controller\LibraryCardsController as LibraryCardsControllerBase;
use VuFind\Exception\LibraryCard as LibraryCardException;

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
