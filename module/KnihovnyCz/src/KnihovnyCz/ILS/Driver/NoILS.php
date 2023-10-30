<?php

namespace KnihovnyCz\ILS\Driver;

use VuFind\Exception\ILS as ILSException;

/**
 * Class NoILS
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Driver
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class NoILS extends \VuFind\ILS\Driver\NoILS
{
    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $username The patron username
     * @param string $password The patron password
     *
     * @throws ILSException
     * @return mixed           Associative array of patron info on successful login,
     * null on unsuccessful login.
     */
    public function patronLogin($username, $password)
    {
        return [
            'cat_username' => $username,
            'cat_password' => $password,
        ];
    }
}
