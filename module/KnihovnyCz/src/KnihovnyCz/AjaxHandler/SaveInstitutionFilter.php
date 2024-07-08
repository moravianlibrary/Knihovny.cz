<?php

declare(strict_types=1);

namespace KnihovnyCz\AjaxHandler;

use KnihovnyCz\Auth\Manager as AuthManager;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\Session\Settings as SessionSettings;

/**
 * "Get Autocomplete Suggestions" AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class SaveInstitutionFilter extends AbstractBase implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * CitacePro service
     */
    protected AuthManager $authManager;

    /**
     * Constructor
     *
     * @param SessionSettings $ss          session settings
     * @param AuthManager     $authManager auth manager
     */
    public function __construct(SessionSettings $ss, AuthManager $authManager)
    {
        $this->sessionSettings = $ss;
        $this->authManager = $authManager;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     * @throws \Exception
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites(); // avoid session write timing bug
        $user = $this->authManager->getUserObject();
        if (!$user) {
            return $this->formatResponse(
                $this->translate('You must be logged in first'),
                self::STATUS_HTTP_NEED_AUTH
            );
        }
        $us = $user->getUserSettings();
        $us->setSavedInstitutions($params->fromPost('institutions'));
        $us->save();
        return $this->formatResponse('');
    }
}
