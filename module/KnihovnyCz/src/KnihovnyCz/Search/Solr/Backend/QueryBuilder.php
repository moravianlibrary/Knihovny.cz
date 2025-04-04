<?php

namespace KnihovnyCz\Search\Solr\Backend;

use Laminas\Config\Config;
use VuFindSearch\ParamBag;
use VuFindSearch\Query\AbstractQuery;
use VuFindSearch\Query\Query;
use VuFindSearch\Query\QueryGroup;

/**
 * SOLR QueryBuilder.
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class QueryBuilder extends \VuFindSearch\Backend\Solr\QueryBuilder
{
    protected $externalQueryParameters = null;

    protected $searchConfig;

    /**
     * Constructor.
     *
     * @param array  $specs                Search handler specifications
     * @param string $defaultDismaxHandler Default dismax handler (if no
     * DismaxHandler set in specs).
     * @param Config $searchConfig         Search configuration
     *
     * @return void
     */
    public function __construct(
        array $specs = [],
        $defaultDismaxHandler = 'dismax',
        Config $searchConfig = null
    ) {
        parent::__construct($specs, $defaultDismaxHandler);
        $this->searchConfig = $searchConfig;
    }

    /**
     * Return SOLR search parameters based on a user query and params.
     * Non-reentrant method.
     *
     * @param AbstractQuery $query  User query
     * @param array         $config Additional configuration
     *
     * @return ParamBag
     */
    public function build(AbstractQuery $query, $config = [])
    {
        $switchToParentQuery = $config['switchToParentQuery'] ?? false;
        $this->externalQueryParameters->reset();
        $this->externalQueryParameters
            ->setSwitchToParentQuery($switchToParentQuery);
        $deduplication = $this->searchConfig->Records->deduplication_type ?? 'default';
        $this->externalQueryParameters->setDeduplication($deduplication);
        $childFilters = $this->searchConfig->ChildRecordFilters ?? null;
        if ($childFilters != null) {
            $childFilter = implode(' AND ', array_values($childFilters->toArray()));
            $this->externalQueryParameters->setChildFilter($childFilter);
        }
        $params = new ParamBag();

        // Add spelling query if applicable -- note that we must set this up before
        // we process the main query in order to avoid unwanted extra syntax:
        if ($this->createSpellingQuery) {
            $params->set(
                'spellcheck.q',
                $this->getLuceneHelper()->extractSearchTerms($query->getAllTerms())
            );
        }

        if ($query instanceof QueryGroup) {
            $finalQuery = $this->reduceQueryGroup($query);
        } else {
            // Clone the query to avoid modifying the original user-visible query
            $finalQuery = clone $query;
            $finalQuery->setString($this->getNormalizedQueryString($query));
        }
        $string = $finalQuery->getString() ?: '*:*';

        // Highlighting is enabled if we have a field list set.
        $highlight = !empty($this->fieldsToHighlight);

        if ($handler = $this->getSearchHandler($finalQuery->getHandler(), $string)) {
            $string = $handler->preprocessQueryString($string);
            if (
                !$handler->hasExtendedDismax()
                && $this->getLuceneHelper()->containsAdvancedLuceneSyntax($string)
            ) {
                $string = $this->createAdvancedInnerSearchString($string, $handler);
                if ($handler->hasDismax()) {
                    $oldString = $string;
                    $string = $handler->createBoostQueryString($string);

                    // If a boost was added, we don't want to highlight based on
                    // the boost query, so we should use the non-boosted version:
                    if ($highlight && $oldString != $string) {
                        $params->set('hl.q', $oldString);
                    }
                }
            } else {
                $string = $handler->createSimpleQueryString($string);
            }
        }
        // Set an appropriate highlight field list when applicable:
        if ($highlight) {
            $filter = $handler ? $handler->getAllFields() : [];
            $params->add('hl.fl', $this->getFieldsToHighlight($filter));
        }
        $params->set('q', $string);
        if ($switchToParentQuery) {
            $childrenQuery = preg_replace('/{!child [^}]+}/', '*:*', $string);
            $params->set('childrenQuery', $childrenQuery);
        }

        // Handle any extra parameters:
        foreach ($this->globalExtraParams as $extraParam) {
            if (empty($extraParam['param']) || empty($extraParam['value'])) {
                continue;
            }
            if (!$this->checkParamConditions($query, null, $extraParam['conditions'] ?? [])) {
                continue;
            }
            foreach ((array)$extraParam['value'] as $value) {
                $params->add($extraParam['param'], $value);
            }
        }

        foreach ($this->externalQueryParameters->getParameters() as $key => $search) {
            $params->add($key, $search);
        }
        return $params;
    }

    /**
     * Set query builder search specs.
     *
     * @param array $specs Search specs
     *
     * @return void
     */
    public function setSpecs(array $specs)
    {
        foreach ($specs as $handler => $spec) {
            if ('GlobalExtraParams' === $handler) {
                $this->globalExtraParams = $spec;
                continue;
            }
            if (isset($spec['ExactSettings'])) {
                $this->exactSpecs[strtolower($handler)] = new SearchHandler(
                    $spec['ExactSettings'],
                    $this->defaultDismaxHandler,
                    $this->getExternalQueryParameters()
                );
                unset($spec['ExactSettings']);
            }
            $this->specs[strtolower($handler)]
                = new SearchHandler(
                    $spec,
                    $this->defaultDismaxHandler,
                    $this->getExternalQueryParameters()
                );
        }
    }

    /**
     * Get external parameters
     *
     * @return \KnihovnyCz\Search\Solr\Backend\ExternalQueryParameters
     */
    public function getExternalQueryParameters()
    {
        if ($this->externalQueryParameters == null) {
            $this->externalQueryParameters = new ExternalQueryParameters();
        }
        return $this->externalQueryParameters;
    }

    /**
     * Get Lucene syntax helper
     *
     * @return LuceneSyntaxHelper
     */
    public function getLuceneHelper(): LuceneSyntaxHelper
    {
        if (null === $this->luceneHelper) {
            $this->luceneHelper = new LuceneSyntaxHelper();
        }
        return $this->luceneHelper;
    }
}
