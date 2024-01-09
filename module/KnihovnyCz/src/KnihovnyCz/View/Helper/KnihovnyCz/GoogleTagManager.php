<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

/**
 * Class GoogleTagManager
 *
 * @category CPK-vufind-6
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GoogleTagManager extends \VuFind\View\Helper\Root\GoogleTagManager
{
    /**
     * Is GTM enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return !empty($this->gtmContainerId);
    }
}
