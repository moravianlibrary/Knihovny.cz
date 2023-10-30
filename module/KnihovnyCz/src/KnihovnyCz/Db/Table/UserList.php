<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Table;

use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Predicate\Like as LikePredicate;
use Laminas\Db\Sql\Select;

/**
 * Class UserList
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Db\Table
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserList extends \VuFind\Db\Table\UserList
{
    /**
     * Get public lists usable as inspiration lists
     *
     * @return ResultSetInterface
     */
    public function getInspirationLists(): ResultSetInterface
    {
        return $this->select(
            function (Select $select) {
                $select->join('user', 'user.id = user_list.user_id', [])
                    ->where(
                        [
                            'user_list.public' => 1,
                            new LikePredicate('user.major', '%widgets%'),
                        ]
                    );
            }
        );
    }
}
