<?php

namespace KnihovnyCz\Db\Table;

/**
 * Class UserCard
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserCard extends \VuFind\Db\Table\UserCard
{
    /**
     * Retrieve a user card object from the database based on eduPersonUniqueId
     * or create new one.
     *
     * @param string $eduPersonUniqueId eduPersonUniqueId
     *
     * @return \VuFind\Db\Row\UserCard
     */
    public function getByEduPersonUniqueId($eduPersonUniqueId)
    {
        $callback = function ($select) use ($eduPersonUniqueId) {
            $select->where->equalTo('edu_person_unique_id', $eduPersonUniqueId);
        };
        $row = $this->select($callback)->current();
        return $row;
    }

    /**
     * Retrieve a user card object from the database based on eduPersonUniqueId
     * or create new one.
     *
     * @param string $eduPersonPrincipalName eduPersonPrincipalName
     *
     * @return \VuFind\Db\Row\UserCard
     */
    public function getByEduPersonPrincipalName($eduPersonPrincipalName)
    {
        $callback = function ($select) use ($eduPersonPrincipalName) {
            $select->where->equalTo('eppn', $eduPersonPrincipalName);
        };
        $row = $this->select($callback)->current();
        return $row;
    }
}
