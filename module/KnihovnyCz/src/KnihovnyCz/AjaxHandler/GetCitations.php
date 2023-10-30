<?php

declare(strict_types=1);

namespace KnihovnyCz\AjaxHandler;

use KnihovnyCz\Service\CitaceProService;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Session\Settings as SessionSettings;

/**
 * Class GetCitations
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GetCitations extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * CitacePro service
     */
    protected CitaceProService $citacePro;

    /**
     * Get citation ajax handler constructor.
     *
     * @param SessionSettings  $ss        Session settings
     * @param CitaceProService $citacePro CitacePro API service
     */
    public function __construct(SessionSettings $ss, CitaceProService $citacePro)
    {
        $this->sessionSettings = $ss;
        $this->citacePro = $citacePro;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params): array
    {
        $this->disableSessionWrites();  // avoid session write timing bug
        $ids = $params->fromPost('recordIds');
        $citeStyle = $params->fromPost('citationStyle');

        $citations = [];
        foreach ($ids as $id) {
            try {
                $citations[$id] = $this->citacePro->getCitation($id, $citeStyle);
            } catch (\Exception $ex) {
                $citations[$id] = false;
            }
        }
        return $this->formatResponse($citations);
    }
}
