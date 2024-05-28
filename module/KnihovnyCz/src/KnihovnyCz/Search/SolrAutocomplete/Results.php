<?php

namespace KnihovnyCz\Search\SolrAutocomplete;

/**
 * Results search model.
 *
 * @category KnihovnyCz
 * @package  Search_Solr
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Results extends \KnihovnyCz\Search\Solr\Results
{
    /**
     * Search backend identifier.
     *
     * @var string
     */
    protected $backendId = 'SolrAutocomplete';
}
