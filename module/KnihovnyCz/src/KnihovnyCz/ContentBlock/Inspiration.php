<?php

/**
 * Class Inspiration
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

use Laminas\Db\Sql\Predicate\Expression;

/**
 * Class Inspiration
 *
 * @category VuFind
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Inspiration implements \VuFind\ContentBlock\ContentBlockInterface
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
    protected $facetField = 'inspiration';

    /**
     * Search type
     *
     * @var string
     */
    protected $searchType = 'AllFields';

    /**
     * Table manager
     *
     * @var \VuFind\Db\Table\PluginManager
     */
    protected $tableManager;

    /**
     * Record loader
     *
     * @var \VuFind\Record\Loader
     */
    protected $recordLoader;

    /**
     * Widget key
     *
     * @var string
     */
    protected $key;

    /**
     * Limit
     *
     * @var int
     */
    protected $limit;

    /**
     * Widget row
     *
     * @var \KnihovnyCz\Db\Row\Widget
     */
    protected $widget;

    /**
     * Widget items
     *
     * @var array
     */
    protected $items;

    /**
     * Constructor
     *
     * @param \VuFind\Db\Table\PluginManager $tables Table manager
     * @param \VuFind\Record\Loader          $loader Record loader
     */
    public function __construct(\VuFind\Db\Table\PluginManager $tables,
        \VuFind\Record\Loader $loader
    ) {
        $this->tableManager = $tables;
        $this->recordLoader = $loader;
    }

    /**
     * Get inspiration list items
     *
     * @return array
     */
    public function getItems()
    {
        if ($this->items === null) {
            $this->items = [];
            $widget = $this->getWidget();
            if (!$widget) {
                return $this->items;
            }
            $widgetContent = $this->tableManager->get(
                \KnihovnyCz\Db\Table\WidgetContent::class
            );
            $select = $widgetContent->getSql()->select();
            $select->where(['widget_id' => $widget->id]);
            $select->limit($this->limit);
            $select->order(new Expression('RAND()'));
            $content = $widgetContent->selectWith($select);

            foreach ($content as $item) {
                try {
                    $this->items[] = $this->recordLoader->load($item->value);
                } catch (\VuFind\Exception\RecordMissing $exception) {
                    // Just omit non-existing records
                }
            }
        }
        return $this->items;
    }

    /**
     * Get widget
     *
     * @return \KnihovnyCz\Db\Row\Widget
     */
    public function getWidget()
    {
        if ($this->widget === null) {
            $widgets = $this->tableManager->get(\KnihovnyCz\Db\Table\Widget::class);
            $this->widget = $widgets->select([ 'name' => $this->key])->current();
        }
        return $this->widget;
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
            'searchType' => $this->searchType,
            'widget' => $this->getWidget(),
            'items' => $this->getItems(),
        ];
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
        [$this->key, $limit] = explode(':', $settings);
        $this->limit = (int)$limit ?? 10;
    }
}
