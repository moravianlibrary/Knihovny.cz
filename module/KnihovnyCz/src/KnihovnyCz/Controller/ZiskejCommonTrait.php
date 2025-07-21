<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use KnihovnyCz\Db\Service\UserCardService;
use Laminas\View\Model\ViewModel;
use VuFind\Exception\LibraryCard;

/**
 * Ziskej common trait
 *
 * @category VuFind
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Site
 */
trait ZiskejCommonTrait
{
    /**
     * Create ViewModel for Ziskej order
     *
     * @param string $template Template name
     *
     * @return ViewModel
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \VuFind\Exception\LibraryCard
     * @throws \Exception
     */
    public function createViewForZiskejOrder(string $template): ViewModel
    {
        //@todo try/catch

        /**
         * User
         *
         * @var ?\KnihovnyCz\Db\Row\User $user
         */
        $user = $this->getUser();
        if (!$user) {
            return $this->forceLogin();
        }

        $userCard = $user->getCardByEppnDomain($this->params()->fromRoute('eppnDomain'));
        if (!$userCard) {
            throw new LibraryCard('Library Card Not Found');
        }

        $this->getDbService(UserCardService::class)->activateLibraryCard($user, $userCard->getId());

        $patron = $this->catalogLogin();
        if (!is_array($patron)) {
            throw new LibraryCard('ILS connection failed');
        }

        /**
         * Ziskej API connector
         *
         * @var \Mzk\ZiskejApi\Api $ziskejApi
         */
        $ziskejApi = $this->serviceLocator->get('Mzk\ZiskejApi\Api');

        $eppn = $userCard->getEppn();
        $ziskejReader = $eppn ? $ziskejApi->getReader($eppn) : null;

        $view = $this->createViewModel(
            [
                'user' => $user,
                'userCard' => $userCard,
                'patron' => $patron,
                'ziskejReader' => $ziskejReader,
                'serverName' => $this->getRequest()->getServer()->SERVER_NAME,
                'entityId' => $this->getRequest()->getServer('Shib-Identity-Provider'),
            ]
        );
        $view->setTemplate($template);

        // getDeduplicatedRecordIds has to be placed after create view model:
        $view->setVariable('dedupedRecordIds', $this->driver->tryMethod('getDeduplicatedRecordIds', [], []));

        return $view;
    }
}
