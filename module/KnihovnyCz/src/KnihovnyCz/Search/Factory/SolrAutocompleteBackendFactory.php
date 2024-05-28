<?php

namespace KnihovnyCz\Search\Factory;

/**
 * Factory for SolrAutocompleteBackendFactory
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class SolrAutocompleteBackendFactory extends SolrDefaultBackendFactory
{
    /**
     * Return deduplication type to use
     *
     * @return string|null
     */
    protected function getDeduplicationType(): ?string
    {
        return 'child';
    }
}
