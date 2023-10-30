<?php

namespace KnihovnyCz\RecordTab;

/**
 * Class SfxAvailability
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SfxAvailability extends \VuFind\RecordTab\AbstractBase
{
    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Availability';
    }
}
