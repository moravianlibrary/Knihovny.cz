<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Table;

use Laminas\Db\Adapter\Adapter;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\Gateway;
use VuFind\Db\Table\PluginManager;

/**
 * Class UserSettings
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserSettings extends Gateway
{
    /**
     * Constructor
     *
     * @param Adapter       $adapter Database adapter
     * @param PluginManager $tm      Table manager
     * @param array         $cfg     Laminas configuration
     * @param RowGateway    $rowObj  Row prototype object (null for default)
     * @param string        $table   Name of database table to interface with
     */
    public function __construct(
        Adapter $adapter,
        PluginManager $tm,
        $cfg,
        ?RowGateway $rowObj = null,
        $table = 'user_settings'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Retrieve a user setting object from the database based on user id.
     *
     * @param int $userId user id
     *
     * @return UserSettings
     */
    public function getOrCreateByUserId($userId)
    {
        $callback = function ($select) use ($userId) {
            $select->where->equalTo('user_id', $userId);
        };
        $row = $this->select($callback)->current();
        if ($row == null) {
            $row = $this->createRow();
            $row->user_id = $userId;
            $row->saved_institutions = '';
            $row->save();
        }
        return $row;
    }
}
