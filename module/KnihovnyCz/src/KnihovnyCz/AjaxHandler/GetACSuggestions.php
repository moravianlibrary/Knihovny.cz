<?php

declare(strict_types=1);

namespace KnihovnyCz\AjaxHandler;

use KnihovnyCz\Autocomplete\Suggester;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Stdlib\Parameters;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\Session\Settings as SessionSettings;

/**
 * "Get Autocomplete Suggestions" AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetACSuggestions extends AbstractBase
{
    /**
     * Autocomplete suggester
     *
     * @var Suggester
     */
    protected $suggester;

    /**
     * Constructor
     *
     * @param SessionSettings $ss        Session settings
     * @param Suggester       $suggester Autocomplete suggester
     */
    public function __construct(SessionSettings $ss, Suggester $suggester)
    {
        $this->sessionSettings = $ss;
        $this->suggester = $suggester;
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
        $this->disableSessionWrites();  // avoid session write timing bug
        $query = new Parameters($params->fromQuery());
        $suggestions = $this->suggester->getSuggestions($query);
        $result = [
            'groups' => $suggestions,
        ];
        return $this->formatResponse($result);
    }
}
