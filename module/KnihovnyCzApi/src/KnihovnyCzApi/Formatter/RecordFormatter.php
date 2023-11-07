<?php

namespace KnihovnyCzApi\Formatter;

/**
 * Record formatter for API responses
 *
 * @category VuFind
 * @package  API
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class RecordFormatter extends \VuFindApi\Formatter\RecordFormatter
{
    /**
     * Get dedup IDs
     *
     * @param \VuFind\RecordDriver\AbstractBase $record Record driver
     *
     * @return array|null
     */
    protected function getDedupIds($record)
    {
        $dedupData = $record->tryMethod('getDeduplicatedRecordIds');
        return !empty($dedupData) ? $dedupData : null;
    }
}
