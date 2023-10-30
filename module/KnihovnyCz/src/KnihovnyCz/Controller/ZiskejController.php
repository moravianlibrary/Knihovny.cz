<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\Http\Response;
use VuFind\Exception\LibraryCard;

/**
 * Class ZiskejController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejController extends AbstractBase
{
    /**
     * Ziskej payment page
     *
     * @return \Laminas\Http\Response
     */
    public function paymentAction(): Response
    {
        $eppnDomain = $this->params()->fromRoute('eppnDomain');
        $ticketId = $this->params()->fromRoute('ticketId');

        return $this->redirect()->toRoute(
            'myresearch-ziskej-mvs-ticket',
            [
                'eppnDomain' => $eppnDomain,
                'ticketId' => $ticketId,
            ]
        );
    }

    /**
     * Ziskej order finished page
     *
     * @return \Laminas\View\Model\ViewModel|mixed
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \VuFind\Exception\LibraryCard
     */
    public function finishedAction()
    {
        //@todo try/catch

        $eppnDomain = $this->params()->fromRoute('eppnDomain');
        $ticketId = $this->params()->fromRoute('ticketId');

        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }

        $userCard = $user->getCardByEppnDomain($eppnDomain);
        if (!$userCard || !$userCard->eppn) {
            throw new LibraryCard('Library Card Not Found');
        }

        /**
         * Ziskej API connector
         *
         * @var \Mzk\ZiskejApi\Api $ziskejApi
         */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $ticket = $ziskejApi->getTicket($userCard->eppn, $ticketId);

        return $this->createViewModel(
            [
                'userCard' => $userCard,
                'ticket' => $ticket,
            ]
        );
    }
}
