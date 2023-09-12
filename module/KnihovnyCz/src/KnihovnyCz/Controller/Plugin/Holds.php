<?php

/**
 * VuFind Action Helper - Holds Support Methods
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
 * @package  Controller_Plugins
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace KnihovnyCz\Controller\Plugin;

use Laminas\Session\SessionManager;
use VuFind\Controller\Plugin\Holds as HoldsBase;
use VuFind\Crypt\HMAC;
use VuFind\Date\Converter as DateConverter;
use VuFind\Validator\CsrfInterface;

/**
 * Action helper to perform holds-related actions
 *
 * @category VuFind
 * @package  Controller_Plugins
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Holds extends HoldsBase
{
    /**
     * Session data
     *
     * @var CsrfInterface
     */
    protected $csrfValidator;

    /**
     * Constructor
     *
     * @param HMAC           $hmac           HMAC generator
     * @param SessionManager $sessionManager Session manager
     * @param DateConverter  $dateConverter  Date converter
     * @param CsrfInterface  $csrfValidator  CSRF validator
     */
    public function __construct(
        HMAC $hmac,
        SessionManager $sessionManager,
        DateConverter $dateConverter,
        CsrfInterface $csrfValidator
    ) {
        parent::__construct($hmac, $sessionManager, $dateConverter);
        $this->csrfValidator = $csrfValidator;
    }

    /**
     * Process cancellation requests.
     *
     * @param \VuFind\ILS\Connection $catalog ILS connection object
     * @param array                  $patron  Current logged in patron
     *
     * @return array                          The result of the cancellation, an
     * associative array keyed by item ID (empty if no cancellations performed)
     */
    public function cancelHolds($catalog, $patron)
    {
        $controller = $this->getController();
        if (!$controller) {
            return [];
        }
        // Retrieve the flashMessenger helper:
        $flashMsg = $controller->flashMessenger();
        $params = $controller->params();

        // Pick IDs to cancel based on which button was pressed:
        $all = $params->fromPost('cancelAll');
        $selected = $params->fromPost('cancelSelected');
        if (!empty($all)) {
            $details = $params->fromPost('cancelAllIDS');
        } elseif (!empty($selected)) {
            // Include cancelSelectedIDS for backwards-compatibility:
            $details = $params->fromPost('selectedIDS')
                ?? $params->fromPost('cancelSelectedIDS');
        } else {
            // No button pushed -- no action needed
            return [];
        }

        if (!$this->csrfValidator->isValid($params->fromPost('csrf'), true)) {
            $flashMsg->addErrorMessage('csrf_validation_failed');
            return [];
        }
        // After successful token verification, clear list to shrink session
        // and prevent double submit:
        $this->csrfValidator->trimTokenList(0);

        if (!empty($details)) {
            // Confirm?
            if ($params->fromPost('confirm') === '0') {
                if ($params->fromPost('cancelAll') !== null) {
                    return $controller->confirm(
                        'hold_cancel_all',
                        $controller->url()->fromRoute('holds-list'),
                        $controller->url()->fromRoute('holds-list'),
                        'confirm_hold_cancel_all_text',
                        [
                            'cancelAll' => 1,
                            'cancelAllIDS' => $params->fromPost('cancelAllIDS')
                        ]
                    );
                } else {
                    return $controller->confirm(
                        'hold_cancel_selected',
                        $controller->url()->fromRoute('holds-list'),
                        $controller->url()->fromRoute('holds-list'),
                        'confirm_hold_cancel_selected_text',
                        [
                            'cancelSelected' => 1,
                            'cancelSelectedIDS' =>
                                $params->fromPost('cancelSelectedIDS')
                        ]
                    );
                }
            }

            // Add Patron Data to Submitted Data
            /* @phpstan-ignore-next-line */
            $cancelResults = $catalog->cancelHolds(
                ['details' => $details, 'patron' => $patron]
            );
            if ($cancelResults == false) {
                $flashMsg->addMessage('hold_cancel_fail', 'error');
            } else {
                $failed = 0;
                foreach ($cancelResults['items'] ?? [] as $item) {
                    if (!$item['success']) {
                        ++$failed;
                    }
                }
                if ($failed) {
                    $msg = $controller->translate(
                        'hold_cancel_fail_items',
                        ['%%count%%' => $failed]
                    );
                    $flashMsg->addErrorMessage($msg);
                }
                if ($cancelResults['count'] > 0) {
                    $msg = $controller->translate(
                        'hold_cancel_success_items',
                        ['%%count%%' => $cancelResults['count']]
                    );
                    $flashMsg->addSuccessMessage($msg);
                }
                return $cancelResults;
            }
        } else {
            $flashMsg->addMessage('hold_empty_selection', 'error');
        }
        return [];
    }

    /**
     * Add an ID to the validation array.
     *
     * @param string $id ID to remember
     *
     * @return void
     */
    public function rememberValidId($id)
    {
        // Do nothing, we rely only on CSRF token for input validation
    }

    /**
     * Validate supplied IDs against remembered IDs. Returns true if all supplied
     * IDs are remembered, otherwise returns false.
     *
     * @param array $ids IDs to validate
     *
     * @return bool
     */
    public function validateIds($ids): bool
    {
        // Do nothing, we rely only on CSRF token for input validation
        return true;
    }
}
