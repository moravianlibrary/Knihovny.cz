<?php

namespace KnihovnyCz\Record\FallbackLoader;

use VuFind\Record\FallbackLoader\Summon as Base;

/**
 * Record loader
 *
 * @category VuFind
 * @package  Record
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class Summon extends Base
{
    /**
     * Fetch a single record (null if not found).
     *
     * @param string $id ID to load
     *
     * @return \VuFindSearch\Response\RecordCollectionInterface
     */
    protected function fetchSingleRecord($id)
    {
        try {
            return parent::fetchSingleRecord($id);
        } catch (\VuFind\Exception\RecordMissing $rm) {
            return new \VuFindSearch\Backend\Summon\Response\RecordCollection([]);
        }
    }
}
