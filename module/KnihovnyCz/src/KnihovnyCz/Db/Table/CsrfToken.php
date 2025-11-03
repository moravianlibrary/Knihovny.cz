<?php

namespace KnihovnyCz\Db\Table;

use KnihovnyCz\Db\Row\User as UserRow;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\Gateway;
use VuFind\Db\Table\PluginManager;

/**
 * Class CsrfToken
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class CsrfToken extends Gateway
{
    use \VuFind\Db\Table\ExpirationTrait;

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
        $table = 'csrf_token'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Retrieve a user object from the database based on eduPersonUniqueId
     * or create new one.
     *
     * @param string $sid   Session ID of current user.
     * @param string $token Token value
     *
     * @return UserRow
     */
    public function findBySessionAndHash(string $sid, string $token)
    {
        return $this->select(
            [
            'session_id' => $sid,
            'token'    => $token,
            ]
        )->current();
    }

    /**
     * Update the select statement to find records to delete.
     *
     * @param Select $select    Select clause
     * @param string $dateLimit Date threshold of an "expired" record in format
     * 'Y-m-d H:i:s'.
     *
     * @return void
     */
    protected function expirationCallback($select, $dateLimit)
    {
        $select->where->lessThan('created', $dateLimit);
    }
}
