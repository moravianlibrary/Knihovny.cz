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
    /**
     * Delete user account if it is confirmed
     *
     * @return mixed|\Zend\Http\Response|\Zend\Http\Response
     */
    public function deleteUserAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!$user = $this->getAuthManager()->isLoggedIn()) {
            $this->flashExceptions($this->flashMessenger());
            return $this->forceLogin();
        }

        $confirm = $this->params()->fromPost(
            'confirm', $this->params()
                ->fromQuery('confirm')
        );

        if ($confirm) {
            $user->delete();
            return $this->logoutAction();
        }

        $this->flashMessenger()->addErrorMessage(
            $this->translate('delete-user-account-not-confirmed')
        );
        return $this->redirect()->toRoute('librarycards-home');
    }
}
