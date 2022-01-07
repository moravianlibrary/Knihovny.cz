<?php

/**
 * Class DocumentTypes
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2019.
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
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\ContentBlock;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;

/**
 * Object Definition for Document types content block
 *
 * @category VuFind
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class DocumentTypes implements \VuFind\ContentBlock\ContentBlockInterface
{
    /**
     * Search class ID to use for retrieving facets.
     *
     * @var string
     */
    protected $searchClassId = 'Solr';

    /**
     * Facet field
     *
     * @var string
     */
    protected $facetField = 'record_format_facet_mv';

    /**
     * Configuration manager
     *
     * @var \VuFind\Config\PluginManager
     */
    protected $configManager;

    /**
     * Content configuration
     *
     * @var \Laminas\Config\Config
     */
    protected $itemsConfig;

    /**
     * DocumentTypes constructor.
     *
     * @param \VuFind\Config\PluginManager $configManager Configuration manager
     */
    public function __construct($configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Store the configuration of the content block.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        if (empty($settings)) {
            throw new ServiceNotCreatedException('Missing configuration.');
        }
        /**
         * Search configuration
         *
         * @var \Laminas\Config\Config
         */
        $searchConfig = $this->configManager->get('searches');
        $config = $searchConfig->get($settings);

        if (empty($config)) {
            throw new ServiceNotCreatedException('Missing configuration.');
        }
        $this->searchClassId = $config->searchClassId ?? $this->searchClassId;
        $this->facetField = $config->facetField ?? $this->facetField;
        $this->itemsConfig = $config->item ?? [];
    }

    /**
     * Get values for content block
     *
     * @return array
     */
    public function getDocumentTypes()
    {
        return array_map(
            function ($item) {
                $itemArray = explode(';', $item);
                return [
                'title' => $itemArray[0] ?? null,
                'description' => $itemArray[1] ?? null,
                'icon' => $itemArray[2] ?? null,
                'value' => $itemArray[3] ?? null,
                ];
            },
            $this->itemsConfig->toArray()
        );
    }

    /**
     * Return context variables used for rendering the block's template.
     *
     * @return array
     */
    public function getContext()
    {
        return [
            'searchClassId' => $this->searchClassId,
            'facetField' => $this->facetField,
            'results' => $this->getDocumentTypes(),
        ];
    }
}
