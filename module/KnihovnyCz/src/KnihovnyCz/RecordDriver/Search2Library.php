<?php

namespace KnihovnyCz\RecordDriver;

/**
 * Knihovny.cz solr library record driver
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class Search2Library extends SolrLibrary
{
    /**
     * Used for identifying search backends
     *
     * @var string
     */
    protected $sourceIdentifier = 'Search2';

    /**
     * Get the Hierarchy Type (false if none)
     *
     * @return string|bool
     */
    public function getHierarchyType()
    {
        return parent::getHierarchyType() ? 'search2' : false;
    }

    /**
     * Get the deduplicated records
     *
     * @return array
     */
    public function getDeduplicatedRecords(): array
    {
        return ['library' => $this->fields['local_ids_str_mv'] ?? []];
    }
}
