<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

/**
 * Account menu view helper
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AccountMenu extends \VuFind\View\Helper\Root\AccountMenu
{
    /**
     * Check whether to show shortloans item
     *
     * @return bool
     */
    public function checkShortloans(): bool
    {
        return $this->checkIlsCapability('getMyShortLoans');
    }

    /**
     * Check whether to show ziskej-mvs item
     *
     * @return bool
     */
    public function checkZiskejMvs(): bool
    {
        return $this->getView()->plugin('ziskejMvs')?->isEnabled() ?? false;
    }

    /**
     * Check whether to show ziskej-edd item
     *
     * @return bool
     */
    public function checkZiskejEdd(): bool
    {
        return $this->getView()->plugin('ziskejEdd')?->isEnabled() ?? false;
    }

    /**
     * Check whether to show librarycards item
     *
     * @return bool
     */
    public function checkLibraryCards(): bool
    {
        $showLibraryCards = $this->getView()->plugin('config')?->get('config')?->Catalog?->show_library_cards ?? true;
        return parent::checkLibraryCards() && $showLibraryCards;
    }

    /**
     * Check whether to show usersettings item
     *
     * @return bool
     */
    public function checkUserSettings(): bool
    {
        return $this->getView()->plugin('accountCapabilities')()?->isUserSettingsEnabled() ?? false;
    }

    /**
     * Check whether to show notifications management item
     *
     * @return bool
     */
    public function checkNotifications(): bool
    {
        return $this->getUser()->couldManageNotifications();
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
