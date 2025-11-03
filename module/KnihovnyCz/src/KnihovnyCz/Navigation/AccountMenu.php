<?php

namespace KnihovnyCz\Navigation;

use KnihovnyCz\Service\PalmknihyApiService;
use KnihovnyCz\Ziskej\ZiskejEdd;
use KnihovnyCz\Ziskej\ZiskejMvs;
use VuFind\Auth\ILSAuthenticator;
use VuFind\Auth\Manager;
use VuFind\Config\AccountCapabilities;
use VuFind\DigitalContent\OverdriveConnector;
use VuFind\ILS\Connection;
use VuFind\Navigation\AccountMenu as Base;

/**
 * Account menu
 *
 * @category VuFind
 * @package  Navigation
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class AccountMenu extends Base
{
    /**
     * Constructor.
     *
     * @param array               $config              Menu configuration
     * @param AccountCapabilities $accountCapabilities Account capabilities
     * @param Manager             $authManager         Authentication manager
     * @param Connection          $ilsConnection       ILS connection
     * @param ILSAuthenticator    $ilsAuthenticator    ILS authenticator
     * @param ?OverdriveConnector $overdriveConnector  Overdrive connector
     * @param ZiskejMvs           $ziskejMvs           Ziskej MVS view plugin
     * @param ZiskejEdd           $ziskejEdd           Ziskej EDD view plugin
     * @param array               $catalogConfig       Catalog config section from main config
     * @param PalmknihyApiService $palmknihyService    Palmknihy API service
     */
    public function __construct(
        array $config,
        protected AccountCapabilities $accountCapabilities,
        protected Manager $authManager,
        protected Connection $ilsConnection,
        protected ILSAuthenticator $ilsAuthenticator,
        protected ?OverdriveConnector $overdriveConnector,
        protected ZiskejMvs $ziskejMvs,
        protected ZiskejEdd $ziskejEdd,
        protected array $catalogConfig,
        protected PalmknihyApiService $palmknihyService
    ) {
        parent::__construct(
            $config,
            $accountCapabilities,
            $authManager,
            $ilsConnection,
            $ilsAuthenticator,
            $overdriveConnector
        );
    }

    /**
     * Check whether to show librarycards item
     *
     * @return bool
     */
    public function checkLibraryCards(): bool
    {
        $showLibraryCards = $this->catalogConfig?->show_library_cards ?? true;
        return parent::checkLibraryCards() && $showLibraryCards;
    }

    /**
     * Check whether to show short loans
     *
     * @return bool
     */
    public function checkShortloans(): bool
    {
        return $this->checkIlsCapability('getMyShortLoans');
    }

    /**
     * Check whether to show Ziskej MVS.
     *
     * @return bool
     */
    public function checkZiskejMvs(): bool
    {
        return $this->ziskejMvs?->isEnabled() ?? false;
    }

    /**
     * Check whether to show Ziskej EDD.
     *
     * @return bool
     */
    public function checkZiskejEdd(): bool
    {
        return $this->ziskejEdd?->isEnabled() ?? false;
    }

    /**
     * Check whether to show check user settings.
     *
     * @return bool
     */
    public function checkUserSettings(): bool
    {
        return $this->accountCapabilities->isUserSettingsEnabled() ?? false;
    }

    /**
     * Check whether to show check notifications.
     *
     * @return bool
     */
    public function checkNotifications(): bool
    {
        return $this->getUser()?->couldManageNotifications() ?? false;
    }

    /**
     * Check whether to show ebooks link.
     *
     * @return bool
     */
    public function checkEbooks(): bool
    {
        return !empty($this->palmknihyService->getEnabledPrefixes($this->getUser()->getLibraryPrefixes()));
    }

    /**
     * Get params for checking ILS capability/function
     *
     * @return array
     */
    protected function getCapabilityParams(): array
    {
        $params = parent::getCapabilityParams();
        $params['user'] = $this->getUser();
        return $params;
    }
}
