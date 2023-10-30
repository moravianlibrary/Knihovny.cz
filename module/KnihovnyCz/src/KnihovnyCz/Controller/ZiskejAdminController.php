<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use KnihovnyCz\Ziskej\ZiskejEdd;
use KnihovnyCz\Ziskej\ZiskejMvs;
use Mzk\ZiskejApi\ResponseModel\Ticket;

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
            if ($this->getRequest()->getPost('ziskejMode')) {
                $cpkZiskejMvs->setMode(
                    $this->getRequest()->getPost('ziskejMode')
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

        $user = $this->getUser();
        if (!$user) {
            return $view;
        }
        $view->setVariable('user', $user);

        $userCards = $user->getLibraryCardsWithILS();

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
                $id = $multiBackend->siglaToSource($ziskejLib->sigla);
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
                    $ziskejReader = $ziskejApi->getReader($eppn);
                    $data[$eppn]['reader'] = $ziskejReader;

                    if ($ziskejReader && $ziskejReader->isActive) {
                        $tickets = $ziskejApi->getTickets($eppn)->getAll();
                        $data[$eppn]['tickets'] = [];
                        /**
                         * ILL ticket model
                         *
                         * @var Ticket $ticket
                         */
                        foreach ($tickets as $ticket) {
                            $ticketId = $ticket->id;
                            $data[$eppn]['tickets'][$ticketId]['ticket'] = $ticket;
                            $data[$eppn]['tickets'][$ticketId]['messages']
                                = $ziskejApi->getMessages($eppn, $ticket->id)
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
