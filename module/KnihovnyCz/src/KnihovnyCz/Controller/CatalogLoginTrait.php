<?php

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
     * @return bool|array|\Laminas\View\Model\ViewModel
     */
    protected function catalogLogin()
    {
        $user = $this->getAuthManager()->getUserObject();
        if ($user == null) {
            return $this->forceLogin();
        }
        $cardId = $this->getCardId();
        if ($cardId != null) {
            $card = $user->getLibraryCard((int)$cardId);
            if ($card != null) {
                $user->cat_username = $card->cat_username;
                $user->cat_password = $card->cat_password;
            }
        }
        $catalog = $this->getILS();
        /* @phpstan-ignore-next-line */
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
