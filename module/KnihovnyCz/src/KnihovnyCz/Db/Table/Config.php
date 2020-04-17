<?php

/**
 * Class Config
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\Db\Table;

use Laminas\Config\Config as LaminasConfig;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Select;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\Gateway;
use VuFind\Db\Table\PluginManager;

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
    public function __construct(Adapter $adapter, PluginManager $tm, $cfg,
        RowGateway $rowObj = null, $table = 'config'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    public function getConfigByFile(string $file): LaminasConfig
    {
        $file = $this->getDataByConfigFile($file);
        $data = [];
        foreach ($file as $item) {
            // We have array key, the configuration option is array with this key:
            if (isset($item->array_key) && $item->array_key !== null) {
                $data[$item->section][$item->item][$item->array_key] = $item->value;
            // We have more then one value, we should turn the value into array
            } elseif (isset($data[$item->section][$item->item]) && is_string($data[$item->section][$item->item])) {
                $data[$item->section][$item->item] = [
                    $data[$item->section][$item->item],
                    $item->value
                ];
            // We have more then one value, and it is array, add new value to array:
            } elseif (isset($data[$item->section][$item->item]) && is_array($data[$item->section][$item->item])) {
                $data[$item->section][$item->item][] = $item->value;
            // Option have single value:
            } else {
                $data[$item->section][$item->item] = $item->value;
            }
        }
        return new LaminasConfig($data);
    }

    protected function getDataByConfigFile(string $filename): ResultSetInterface
    {
        $file = $this->select(function (Select $select) use ($filename) {
            $select
                ->columns(['id', 'item', 'array_key', 'value'])
                ->join('config_files', 'file_id = config_files.id', [])
                ->join('config_sections', 'section_id = config_sections.id', ['section' => 'section_name'] )
                ->where(['config_files.file_name' => $filename, 'active' => 1])
                ->order(['item', 'order']);
        });
        return $file;
    }
}
