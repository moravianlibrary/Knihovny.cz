<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordTab;

/**
 * Class HoldingILS
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordTab
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class HoldingsILS extends \VuFind\RecordTab\HoldingsILS
{
    /**
     * Is this tab initially visible?
     *
     * @return bool
     */
    public function isActive()
    {
        $hasHoldings = $this->getRecordDriver()->tryMethod('hasOfflineHoldings', [], false);
        $hasSerialLinks = $this->getRecordDriver()->tryMethod('getSerialLinks', [], false);
        $hasHoldingsNotice = !empty($this->getRecordDriver()->tryMethod('getHoldingsNotice', [], false));
        return $this->hideWhenEmpty ? ($hasHoldings || $hasSerialLinks || $hasHoldingsNotice) : true;
    }
}
