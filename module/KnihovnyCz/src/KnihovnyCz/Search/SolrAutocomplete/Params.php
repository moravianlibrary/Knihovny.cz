<?php

namespace KnihovnyCz\Search\SolrAutocomplete;

use VuFind\Search\Solr\HierarchicalFacetHelper;

/**
 * Solr Search Parameters
 *
 * @category KnihovnyCz
 * @package  Search_Solr
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Params extends \KnihovnyCz\Search\Solr\Params
{
    /**
     * Constructor
     *
     * @param \VuFind\Search\Base\Options  $options      Options to use
     * @param \VuFind\Config\PluginManager $configLoader Config loader
     * @param HierarchicalFacetHelper      $facetHelper  Hierarchical facet helper
     * @param \KnihovnyCz\Geo\Parser       $parser       Geo parser
     * @param \KnihovnyCz\Date\Converter   $converter    Date converter
     */
    public function __construct(
        $options,
        \VuFind\Config\PluginManager $configLoader,
        ?HierarchicalFacetHelper $facetHelper = null,
        ?\KnihovnyCz\Geo\Parser $parser = null,
        ?\KnihovnyCz\Date\Converter $converter = null
    ) {
        parent::__construct($options, $configLoader, $facetHelper, $parser, $converter);
        $this->deduplicationType = 'child';
    }
}
