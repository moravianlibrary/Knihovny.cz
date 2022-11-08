<?php

/**
 * Class InstConfigs
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

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager;

/**
 * Class InstConfigs
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InstConfigs extends \VuFind\Db\Table\Gateway
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
        $table = 'inst_configs'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Retrieves config specified by a source
     *
     * Returns empty array if no configuration found for an institution
     *
     * @param \KnihovnyCz\Db\Row\InstSources $source Instance identifier
     *
     * @return array
     */
    public function getConfig(\KnihovnyCz\Db\Row\InstSources $source)
    {
        $config = [];
        $templateSource = '!' . $source->driver;
        $this->applyConfig($config, $templateSource);
        $this->applyConfig($config, $source->source);
        return $config;
    }

    /**
     * Add config on top of current one
     *
     * @param array  $config Configuration array
     * @param string $source Instance identifier
     *
     * @return array
     */
    protected function applyConfig(&$config, $source)
    {
        $dbConfig = $this->select(
            function (Select $select) use ($source) {
                $select
                    ->columns(['id', 'value', 'array_key'])
                    ->join('inst_sources', 'source_id = inst_sources.id')
                    ->join(
                        'inst_keys',
                        'key_id = inst_keys.id',
                        ['key' => 'key_name']
                    )->join(
                        'inst_sections',
                        'inst_keys.section_id = inst_sections.id',
                        ['section' => 'section_name']
                    )->where(['inst_sources.source' => $source]);
            }
        );

        foreach ($dbConfig as $cfgItem) {
            $section = $cfgItem['section'];
            $key = $cfgItem['key'];
            $value = $cfgItem['value'];
            if (isset($cfgItem['array_key']) && $cfgItem['array_key'] !== null) {
                $config[$section][$key][$cfgItem['array_key']] = $value;
            } else {
                $config[$section][$key] = $value;
            }
        }
        return $config;
    }
}
