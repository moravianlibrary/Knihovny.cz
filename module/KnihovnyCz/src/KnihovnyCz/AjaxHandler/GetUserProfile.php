<?php

namespace KnihovnyCz\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\AjaxHandler\AbstractIlsAndUserAction;

/**
 * "Get User Profile" AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetUserProfile extends AbstractIlsAndUserAction
{
    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, internal status code, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();  // avoid session write timing bug
        $patron = $this->ilsAuthenticator->storedCatalogLogin();
        if (!$patron) {
            return $this->formatResponse('', self::STATUS_HTTP_NEED_AUTH);
        }
        if (!$this->ils->checkCapability('getMyProfile')) {
            return $this->formatResponse('', self::STATUS_HTTP_ERROR);
        }
        $result = $this->ils->getMyProfile($patron);
        $status = [
            'expired' => $result['expired'] ?? false,
        ];
        return $this->formatResponse($status);
    }
}
