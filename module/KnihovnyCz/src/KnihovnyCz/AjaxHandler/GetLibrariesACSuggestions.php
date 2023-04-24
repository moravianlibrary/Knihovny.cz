<?php

/**
 * "Get Autocomplete Suggestions" AJAX handler
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

declare(strict_types=1);

namespace KnihovnyCz\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Stdlib\Parameters;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\Search\Results\PluginManager as ResultsManager;

/**
 * "Get Autocomplete Suggestions" AJAX handler
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetLibrariesACSuggestions extends AbstractBase implements
    TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * ResultsManager
     *
     * @var resultsManager
     */
    protected $resultsManager;

    /**
     * Constructor
     *
     * @param ResultsManager $resultsManager Results Manager
     */
    public function __construct(ResultsManager $resultsManager)
    {
        $this->resultsManager = $resultsManager;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $search = $this->mungeQuery($params->fromQuery('q'));
        $query = "name_autocomplete:($search) OR town_autocomplete:($search)";
        if (strlen($search) == 6) {
            $query .= " OR sigla_search_txt:($search)";
        }
        $lookfor = "portal_facet_mv:\"KNIHOVNYCZ_YES\" AND ($query)";
        $results = $this->resultsManager->get('Search2');
        $paramsObj = $results->getParams();
        $paramsObj->getOptions()->disableHighlighting();
        $paramsObj->getOptions()->spellcheckEnabled(false);
        $paramsObj->getOptions()->setLimitOptions([100]);
        $paramsObj->initFromRequest(new Parameters(['lookfor' => $lookfor ]));
        $libraries = [];
        foreach ($results->getResults() as $library) {
            $filter = $library->getBookSearchFilter();
            if ($filter != null && !array_key_exists($filter, $libraries)) {
                $libraries[$filter] = $this->translate('Source::' . $filter);
            }
        }
        $response = [];
        foreach ($libraries as $filter => $label) {
            $response[] = [
                'value' =>  $filter,
                'label' =>  $label,
            ];
        }
        return $this->formatResponse($response);
    }

    /**
     * Process the user query to make it suitable for a Solr query.
     *
     * @param string $query Incoming user query
     *
     * @return string       Processed query
     */
    protected function mungeQuery($query)
    {
        $forbidden = [':', '(', ')', '*', '+', '"'];
        return str_replace($forbidden, " ", $query);
    }
}
