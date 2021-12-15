<?php

/**
 * Class CatalogLoginTrait
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
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

/**
 * Class CatalogLoginTrait
 *
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait CatalogLoginTrait
{
    /**
     * Does the user have catalog credentials available?  Returns associative array
     * of patron data if so, otherwise forwards to appropriate login prompt and
     * returns false. If there is an ILS exception, a flash message is added and
     * a newly created ViewModel is returned.
     *
     * @return bool|array|ViewModel
     */
    protected function catalogLogin()
    {
        $user = $this->getAuthManager()->isLoggedIn();
        if ($user == false) {
            return $this->forceLogin();
        }
        $cardId = $this->getCardId();
        if ($cardId != null) {
            $card = $user->getLibraryCard($cardId);
            if ($card != null) {
                $user->cat_username = $card->cat_username;
                $user->cat_password = $card->cat_password;
            }
        }
        $catalog = $this->getILS();
        $patron = $catalog->patronLogin(
            $user->cat_username,
            $user->getCatPassword()
        );
        return $patron;
    }

    /**
     * Return card id to use
     *
     * @return string|null
     */
    protected function getCardId()
    {
        return $this->getRequest()->getQuery(
            'cardId',
            $this->getRequest()->getPost('cardId')
        );
    }
}
