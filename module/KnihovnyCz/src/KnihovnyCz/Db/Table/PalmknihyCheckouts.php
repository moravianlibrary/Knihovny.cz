<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Table;

use Laminas\Db\Adapter\Adapter;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager;

/**
 * Class PalmknihyCheckouts
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class PalmknihyCheckouts extends \VuFind\Db\Table\Gateway
{
    /**
     * Constructor
     *
     * @param Adapter         $adapter Database adapter
     * @param PluginManager   $tm      Table manager
     * @param array           $cfg     Laminas configuration
     * @param RowGateway|null $rowObj  Row prototype object (null for default)
     * @param string          $table   Name of database table to interface with
     */
    public function __construct(
        Adapter $adapter,
        PluginManager $tm,
        $cfg,
        ?RowGateway $rowObj = null,
        $table = 'palmknihy_checkouts'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }
}
