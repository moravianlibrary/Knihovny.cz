<?php

declare(strict_types=1);

namespace KnihovnyCz\Config;

use VuFind\Config\AccountCapabilities as Base;

/**
 * Class to determine which account capabilities are available, based on
 * configuration and other factors.
 *
 * @category VuFind
 * @package  KnihovnyCz\Config
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

class AccountCapabilities extends Base
{
    /**
     * Are user settings enabled?
     *
     * @return bool
     */
    public function isUserSettingsEnabled()
    {
        return $this->config->Account->user_settings ?? true;
    }
}
