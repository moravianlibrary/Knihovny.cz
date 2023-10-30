<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Row;

use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\DbTableAwareInterface;

/**
 * Class UserListCategories
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Db\Row
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserListCategories extends RowGateway implements DbTableAwareInterface
{
    use \VuFind\Db\Table\DbTableAwareTrait;

    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     *
     * @return void
     */
    public function __construct(\Laminas\Db\Adapter\Adapter $adapter)
    {
        parent::__construct('category', 'user_list_categories', $adapter);
    }
}
