<?php

namespace KnihovnyCz\Db\Table;

use Laminas\Db\Adapter\Adapter;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager;

/**
 * Class Search
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Search extends \VuFind\Db\Table\Search
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
        $table = 'search'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Get an array of rows for migration to new format
     *
     * @return array      Matching SearchEntry objects.
     */
    public function getSearchesForMigration()
    {
        $callback = function ($select) {
            $select->where->equalTo('migrate', 2);
            $select->order('created');
        };
        return $this->select($callback);
    }

    /**
     * Get an array of rows for migration to new format
     *
     * @return array      Matching SearchEntry objects.
     */
    public function getSearchesForUpdate()
    {
        $callback = function ($select) {
            $select->where->equalTo('migrate', 1);
            $select->order('created');
        };
        return $this->select($callback);
    }
}
