<?php

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
            if ($filter != null ) {
                $libraries[] = $filter;
            }
        }
        $libraries = array_unique($libraries);
        $response = [];
        foreach ($libraries as $filter) {
            $response[] = [
                'value' =>  $filter,
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
        return str_replace($forbidden, ' ', $query);
    }
}
