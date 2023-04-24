<?php

/**
 * SolrAuthorityEnricher
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2022.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @category Knihovny.cz
 * @package  Autocomplete
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:autosuggesters Wiki
 */

namespace KnihovnyCz\Autocomplete;

use KnihovnyCz\View\Helper\KnihovnyCz\RecordLinker as RecordLinker;
use Laminas\View\Renderer\PhpRenderer as Renderer;
use VuFind\Search\Results\PluginManager as Results;
use VuFindSearch\Command\SearchCommand;
use VuFindSearch\Service as SearchService;

/**
 * SolrAuthorityEnricher
 *
 * This class provides suggestions by using the local Solr index and enrich
 * them with link to authority record.
 *
 * @category Knihovny.cz
 * @package  Autocomplete
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:autosuggesters Wiki
 */
class SolrAuthorityEnricher extends SolrPrefix
{
    /**
     * Max number of results to fetch
     *
     * @var int
     */
    protected const LIMIT = 100;

    /**
     * Regular expression to check if the authority name is considered unique
     *
     * @var string
     */
    protected const UNIQUE_AUTHOR_REGEXP = '/(\d+)\\-|\\-(\d+)/';

    /**
     * Search service
     *
     * @var SearchService
     */
    protected $searchService;

    /**
     * Record link helper
     *
     * @var RecordLinker
     */
    protected $recordLinker;

    /**
     * Renderer
     *
     * @var Renderer
     */
    protected $renderer;

    /**
     * Search field
     *
     * @var string
     */
    protected $searchField = 'author_autocomplete';

    /**
     * Solr record id
     *
     * @var string
     */
    protected $recordIdField = 'local_ids_str_mv';

    /**
     * Filter to apply when enriching results from autocomplete
     *
     * @var string
     */
    protected $filter = 'record_format_facet_mv:1/OTHER/PERSON/';

    /**
     * Constructor
     *
     * @param Results          $results       Results plugin manager
     * @param SuggestionFilter $filter        Suggestion filter
     * @param SearchService    $searchService Search service plugin manager
     * @param RecordLinker     $recordLinker  Record linker
     * @param Renderer         $renderer      Renderer
     */
    public function __construct(
        \VuFind\Search\Results\PluginManager $results,
        SuggestionFilter $filter,
        \VuFindSearch\Service $searchService,
        \KnihovnyCz\View\Helper\KnihovnyCz\RecordLinker $recordLinker,
        \Laminas\View\Renderer\PhpRenderer $renderer
    ) {
        parent::__construct($results, $filter);
        $this->searchService = $searchService;
        $this->recordLinker = $recordLinker;
        $this->renderer = $renderer;
    }

    /**
     * Get suggestions
     *
     * This method returns an array of strings matching the user's query for
     * display in the autocomplete box.
     *
     * @param string $query The user query
     *
     * @return array        The suggestions for the provided query
     */
    public function getSuggestions($query)
    {
        return $this->enrich(parent::getSuggestions($query));
    }

    /**
     * Enrich authors with link to authority record
     *
     * @param array $authors authors
     *
     * @return array enriched authors
     */
    protected function enrich($authors)
    {
        $fields = ['id', $this->searchField, $this->recordIdField ];
        $params = new \KnihovnyCz\Search\ParamBag(
            [
                'fq' => [$this->filter],
                'fl' => implode(',', $fields),
            ]
        );
        $params->setApplyChildFilter(false);
        $fullQuery = new \VuFindSearch\Query\QueryGroup('OR');
        foreach ($authors as $author) {
            $value = $author['value'];
            if (!$this->isUnique($value)) {
                continue;
            }
            $escaped = str_replace(['(', ')'], ' ', $value);
            $query = $this->searchField . ':(' . $escaped . ')';
            $query = new \VuFindSearch\Query\Query($query);
            $fullQuery->addQuery($query);
        }
        // Fetch more results - different authors with the same name
        $command = new SearchCommand('Solr', $fullQuery, 0, self::LIMIT, $params);
        $idsByAuthor = [];
        $searchResults = $this->searchService->invoke($command)->getResult();
        foreach ($searchResults->getRecords() as $record) {
            $fields = $record->getRawData();
            $values = $fields[$this->searchField] ?? [];
            foreach ($values as $value) {
                if (!isset($idsByAuthor[$value])) {
                    $idsByAuthor[$value] = [];
                }
                $recordIds = $fields[$this->recordIdField] ?? [];
                if (!empty($recordIds)) {
                    $idsByAuthor[$value][] = $fields[$this->recordIdField][0];
                }
            }
        }
        $newResults = [];
        foreach ($authors as $author) {
            $label = $author['label'];
            $value = $author['value'];
            $recordIds = $idsByAuthor[$value] ?? [];
            // show link to authority record if only one author was found
            if (count($recordIds) == 1) {
                $label = $this->renderer->render(
                    'autocomplete/authority',
                    [
                        'label' => $label,
                        'value' => $value,
                        'link'  => $this->recordLinker->getUrl($recordIds[0])
                    ]
                );
            }
            $newResults[] = [
                'label' => $label,
                'value' => $value,
            ];
        }
        return $newResults;
    }

    /**
     * Is the authority name unique?
     *
     * @param string $author author name
     *
     * @return bool
     */
    protected function isUnique($author)
    {
        return preg_match(self::UNIQUE_AUTHOR_REGEXP, $author) == 1;
    }
}
