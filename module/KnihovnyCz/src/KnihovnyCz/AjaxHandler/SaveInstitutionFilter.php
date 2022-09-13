<?php
declare(strict_types=1);
/**
 * Save institution filter AJAX handler
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
 * @package  AJAX
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace KnihovnyCz\AjaxHandler;

use KnihovnyCz\Auth\Manager as AuthManager;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\I18n\Translator\TranslatorAwareInterface;
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
class SaveInstitutionFilter extends \VuFind\AjaxHandler\AbstractBase
    implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

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
        $user = $this->authManager->isLoggedIn();
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
