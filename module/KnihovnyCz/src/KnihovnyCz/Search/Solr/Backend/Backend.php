<?php

namespace KnihovnyCz\Search\Solr\Backend;

use VuFindSearch\Backend\Solr\Backend as Base;
use VuFindSearch\Backend\Solr\QueryBuilder;
use VuFindSearch\ParamBag;
use VuFindSearch\Query\AbstractQuery;

/**
 * SOLR backend.
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class Backend extends Base
{
    /**
     * Perform a search and return a raw response.
     *
     * @param AbstractQuery $query  Search query
     * @param int           $offset Search offset
     * @param int           $limit  Search limit
     * @param ParamBag      $params Search backend parameters
     *
     * @return string
     */
    public function rawJsonSearch(
        AbstractQuery $query,
        $offset,
        $limit,
        ?ParamBag $params = null
    ) {
        $params = $params ?: new ParamBag();
        $this->injectResponseWriter($params);
        $params->set('rows', $limit);
        $params->set('start', $offset);
        $switchToParentQuery = $params->contains('switchToParentQuery', true);
        if ($switchToParentQuery) {
            $params->remove('switchToParentQuery');
        }
        $params->mergeWith(
            $this->getQueryBuilder()
                ->build($query, compact('switchToParentQuery'))
        );
        return $this->connector->search($params);
    }

    /**
     * Set the query builder.
     *
     * @param QueryBuilder $queryBuilder Query builder
     *
     * @return void
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Return query builder.
     *
     * Lazy loads an empty default QueryBuilder if none was set.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new QueryBuilder();
        }
        return $this->queryBuilder;
    }
}
