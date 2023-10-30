<?php

namespace KnihovnyCz\Search\Solr;

use KnihovnyCz\Search\Factory\UrlQueryHelperFactory;
use VuFind\Record\Loader;
use VuFindSearch\Service as SearchService;

/**
 * Results search model.
 *
 * @category KnihovnyCz
 * @package  Search_Solr
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Results extends \VuFind\Search\Solr\Results
{
    /**
     * Constructor
     *
     * @param \VuFind\Search\Base\Params $params        Object representing user
     * search parameters.
     * @param SearchService              $searchService Search service
     * @param Loader                     $recordLoader  Record loader
     */
    public function __construct(
        Params $params,
        SearchService $searchService,
        Loader $recordLoader
    ) {
        parent::__construct($params, $searchService, $recordLoader);
    }

    /**
     * Get URL query helper factory
     *
     * @return UrlQueryHelperFactory
     */
    protected function getUrlQueryHelperFactory()
    {
        $this->urlQueryHelperFactory = new UrlQueryHelperFactory();
        return $this->urlQueryHelperFactory;
    }
}
