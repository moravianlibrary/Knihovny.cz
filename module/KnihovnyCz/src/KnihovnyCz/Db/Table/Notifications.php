<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Table;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Select;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager;

/**
 * Class Notifications
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Notifications extends \VuFind\Db\Table\Gateway
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
        $table = 'notifications'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Get current notifications to show
     *
     * @param string $language UI language
     *
     * @return ResultSetInterface
     */
    public function getActiveNotifications(string $language = 'cs'): ResultSetInterface
    {
        return $this->select(
            function (Select $select) use ($language) {
                $select->where([
                    'visibility' => 1,
                    'language' => $language,
                ]);
                $select->order('priority DESC');
            }
        );
    }

    /**
     * Get current notifications to show
     *
     * @return ResultSetInterface
     */
    public function getAllNotifications(): ResultSetInterface
    {
        return $this->select(
            function (Select $select) {
                $select->order('create_date DESC');
            }
        );
    }
}
