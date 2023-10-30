<?php

namespace KnihovnyCz\Db\Row;

use VuFind\Db\Row\UserCard as Base;

/**
 * Class UserCard
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Row
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 *
 * @property int     $id
 * @property int     $user_id
 * @property string  $card_name
 * @property string  $cat_username
 * @property ?string $cat_password
 * @property ?string $cat_pass_enc
 * @property string  $home_library
 * @property string  $created
 * @property string  $saved
 * @property ?string $eppn
 * @property ?string $major
 */
class UserCard extends Base
{
    /**
     * Get EduPersonPrincipalName scope
     *
     * @return string|null
     */
    public function getEppnDomain(): ?string
    {
        $array = explode('@', $this->eppn ?? '');
        return end($array) ?? null;
    }
}
