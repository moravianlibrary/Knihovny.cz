<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordTab;

/**
 * Class Cited
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordTab
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Cites extends \VuFind\RecordTab\AbstractBase
{
    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Cites';
    }

    /**
     * Is this tab visible?
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->getRecordDriver()->tryMethod('getCitedDocuments');
    }
}
