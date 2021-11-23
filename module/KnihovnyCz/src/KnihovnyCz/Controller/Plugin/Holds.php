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

use VuFind\Controller\Plugin\Holds as HoldsBase;

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
