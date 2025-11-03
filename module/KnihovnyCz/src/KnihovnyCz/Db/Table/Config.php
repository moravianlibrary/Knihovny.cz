<?php

namespace KnihovnyCz\Db\Table;

use Laminas\Config\Config as LaminasConfig;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Select;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\Gateway;
use VuFind\Db\Table\PluginManager;

/**
 * Class Config
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Config extends Gateway
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
        $table = 'config'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Get config by file
     *
     * @param string $file Filename as known in original VuFind
     *
     * @return LaminasConfig
     */
    public function getConfigByFile(string $file): LaminasConfig
    {
        $file = $this->getDataByConfigFile($file);
        $data = [];
        /**
         * Configuration item
         *
         * @var \KnihovnyCz\Db\Row\Config $item
         */
        foreach ($file as $item) {
            // The type is array:
            if ($item->type == 'array') {
                // we have array_key, use it:
                if (isset($item->array_key) && $item->array_key !== null) {
                    $data[$item->section][$item->item][$item->array_key]
                        = $item->value;
                } else { // We do not have array_key, just leave it on numbers
                    $data[$item->section][$item->item][] = $item->value;
                }
            } else { // Type is string:
                $data[$item->section][$item->item] = $item->value;
            }
        }
        return new LaminasConfig($data);
    }

    /**
     * Get configuration data from database
     *
     * @param string $filename Filename as known in original VuFind
     *
     * @return ResultSetInterface
     */
    protected function getDataByConfigFile(string $filename): ResultSetInterface
    {
        $file = $this->select(
            function (Select $select) use ($filename) {
                $select
                    ->columns(['id', 'array_key', 'value'])
                    ->join('config_files', 'file_id = config_files.id', [])
                    ->join(
                        'config_sections',
                        'section_id = config_sections.id',
                        ['section' => 'section_name']
                    )->join(
                        'config_items',
                        'item_id = config_items.id',
                        ['type' => 'type', 'item' => 'name']
                    )->where(['config_files.file_name' => $filename, 'active' => 1])
                    ->order(['item', 'order']);
            }
        );
        return $file;
    }
}
