<?php

declare(strict_types=1);
/**
 * Class ZiskejAdminController
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

use KnihovnyCz\Ziskej\ZiskejEdd;
use KnihovnyCz\Ziskej\ZiskejMvs;
use Mzk\ZiskejApi\ResponseModel\Reader;
use Mzk\ZiskejApi\ResponseModel\Ticket;
use Mzk\ZiskejApi\ResponseModel\TicketsCollection;

/**
 * Class ZiskejAdminController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejAdminController extends AbstractBase
{
    /**
     * Home action
     *
     * @return \Laminas\Http\Response|\Laminas\View\Model\ViewModel
     *
     * @throws \Http\Client\Exception
     * @throws \VuFind\Exception\LibraryCard
     */
    public function homeAction()
    {
        $view = $this->createViewModel();

        /**
         * Ziskej ILL model
         *
         * @var \KnihovnyCz\Ziskej\ZiskejMvs $cpkZiskejMvs
         */
        $cpkZiskejMvs = $this->serviceLocator->get(ZiskejMvs::class);

        /**
         * Ziskej electronic copy model
         *
         * @var \KnihovnyCz\Ziskej\ZiskejEdd $cpkZiskejEdd
         */
        $cpkZiskejEdd = $this->serviceLocator->get(ZiskejEdd::class);

        if ($this->getRequest()->isPost()) {
            if ($this->getRequest()->getPost('ziskejMvsMode')) {
                $cpkZiskejMvs->setMode(
                    $this->getRequest()->getPost('ziskejMvsMode')
                );
            }
            if ($this->getRequest()->getPost('ziskejEddMode')) {
                $cpkZiskejEdd->setMode(
                    $this->getRequest()->getPost('ziskejEddMode')
                );
            }
            $this->flashMessenger()->addMessage(
                'Ziskej::message_ziskej_mode_saved',
                'success'
            );
            return $this->redirect()->refresh();
        }

        if (!$cpkZiskejMvs->isEnabled() && !$cpkZiskejEdd->isEnabled()) {
            return $view;
        }

        /**
         * User
         *
         * @var \KnihovnyCz\Db\Row\User $user
         */
        $user = $this->getUser();
        if (!$user) {
            return $view;
        }
        $view->setVariable('user', $user);

        $userCards = $user->getLibraryCards();

        /**
         * MultiBackend ILS driver
         *
         * @var \KnihovnyCz\ILS\Driver\MultiBackend $multiBackend
         */
        $multiBackend = $this->getILS()->getDriver();

        try {
            /**
             * Ziskej API connector
             *
             * @var \Mzk\ZiskejApi\Api $ziskejApi
             */
            $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

            /**
             * Codes of all libraries active in Ziskej ILL system
             *
             * @var string[] $ziskejLibsCodes
             */
            $ziskejLibsCodes = [];

            $ziskejLibs = $ziskejApi->getLibrariesAll();
            foreach ($ziskejLibs->getAll() as $ziskejLib) {
                $id = $multiBackend->siglaToSource($ziskejLib->getSigla());
                if (!empty($id)) {
                    $ziskejLibsCodes[] = $id;
                }
            }

            $data = [];

            /**
             * User library card
             *
             * @var \KnihovnyCz\Db\Row\UserCard $userCard
             */
            foreach ($userCards as $userCard) {
                $eppn = $userCard->eppn;
                if (!$eppn) {
                    continue;
                }

                $inZiskej = in_array($userCard->home_library, $ziskejLibsCodes);
                $data[$eppn]['isLibraryInZiskej'] = $inZiskej;

                if ($inZiskej) {
                    /**
                     * Reader model
                     *
                     * @var Reader $ziskejReader
                     */
                    $ziskejReader = $ziskejApi->getReader($eppn);
                    $data[$eppn]['reader'] = $ziskejReader;

                    if ($ziskejReader && $ziskejReader->isActive()) {
                        /**
                         * ILL ticket collection model
                         *
                         * @var TicketsCollection $tickets
                         */
                        $tickets = $ziskejApi->getTickets($eppn)->getAll();
                        $data[$eppn]['tickets'] = [];
                        /**
                         * ILL ticket model
                         *
                         * @var Ticket $ticket
                         */
                        foreach ($tickets as $ticket) {
                            $ticketId = $ticket->getId();
                            $data[$eppn]['tickets'][$ticketId]['ticket'] = $ticket;
                            $data[$eppn]['tickets'][$ticketId]['messages']
                                = $ziskejApi->getMessages($eppn, $ticket->getId())
                                ->getAll();
                        }
                    }
                }
            }

            $view->setVariable('data', $data);
        } catch (\Exception $ex) {
            $this->flashMessenger()->addMessage($ex->getMessage(), 'warning');
            //@todo zapnout na produkci
            //$this->flashMessenger()->addMessage(
            //    'Ziskej::warning_api_disconnected', 'warning'
            //);
        }

        return $view;
    }
}
