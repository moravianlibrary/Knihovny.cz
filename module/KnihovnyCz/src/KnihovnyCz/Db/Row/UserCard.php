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
 * @property string  $edu_person_unique_id
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

    /**
     * Get edu person unique id
     *
     * @return ?string
     */
    public function getEduPersonUniqueId(): ?string
    {
        return $this->edu_person_unique_id;
    }

    /**
     * Set edu person unique id
     *
     * @param string $eduPersonUniqueId new edu person unique id
     *
     * @return UserCard
     */
    public function setEduPersonUniqueId(string $eduPersonUniqueId): UserCard
    {
        $this->edu_person_unique_id = $eduPersonUniqueId;
        return $this;
    }

    /**
     * Get eppn
     *
     * @return ?string
     */
    public function getEppn(): ?string
    {
        return $this->eppn;
    }

    /**
     * Set eppn
     *
     * @param string $eppn new eduPersonPrincipalName
     *
     * @return UserCard
     */
    public function setEppn(string $eppn): UserCard
    {
        $this->eppn = $eppn;
        return $this;
    }

    /**
     * Get library card prefix and username
     *
     * @return string[] Array of two strings, first is prefix, and second is username. Array of nulls when username
     * cannot be split
     */
    public function getPrefixAndUsername(): array
    {
        $prefixAndUsername = explode('.', $this->getCatUsername(), 2);
        if (count($prefixAndUsername) === 2) {
            return [$prefixAndUsername[0], $prefixAndUsername[1]];
        }
        return [null, null];
    }
}
