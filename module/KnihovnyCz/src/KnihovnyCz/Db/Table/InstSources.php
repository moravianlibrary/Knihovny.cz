<?php

namespace KnihovnyCz\Db\Table;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager;

/**
 * Class InstSources
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InstSources extends \VuFind\Db\Table\Gateway
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
        RowGateway $rowObj = null,
        $table = 'inst_sources'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Get information about instance
     *
     * @param string $shortcut Source/instance identifier
     *
     * @return \KnihovnyCz\Db\Row\InstSources|null
     */
    public function getSource(string $shortcut)
    {
        return $this->select(
            function (Select $select) use ($shortcut) {
                $select
                    ->columns(['id', 'library_name', 'source', 'driver'])
                    ->where(['source' => $shortcut]);
            }
        )->current();
    }
}
