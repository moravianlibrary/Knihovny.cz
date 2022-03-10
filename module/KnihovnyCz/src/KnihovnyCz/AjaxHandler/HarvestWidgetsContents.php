<?php
declare(strict_types=1);

/**
 * Class HarvestWidgetsContents
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2022.
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\AjaxHandler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\AjaxHandler;

use KnihovnyCz\Db\Table\Widget;
use KnihovnyCz\Db\Table\WidgetContent;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\AjaxHandler\AbstractBase;

/**
 * Class HarvestWidgetsContents
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\AjaxHandler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class HarvestWidgetsContents extends AbstractBase
{
    /**
     * Widget table
     *
     * @var Widget
     */
    protected Widget $widgets;

    /**
     * Widget content table
     *
     * @var WidgetContent
     */
    protected WidgetContent $content;

    /**
     * Constructor
     *
     * @param Widget        $widgets        Widget table
     * @param WidgetContent $widgetContents Widget content table
     */
    public function __construct(
        Widget $widgets,
        WidgetContent $widgetContents
    ) {
        $this->widgets = $widgets;
        $this->content = $widgetContents;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     * @throws \Exception
     */
    public function handleRequest(Params $params)
    {
        $widgets = $this->widgets->select();
        $data = [];
        foreach ($widgets as $widget) {
            $contents = $this->content->select(['widget_id' => $widget['id']]);
            $data[] = [
                'list' => $widget['name'],
                'items' => array_column($contents->toArray(), 'value'),
            ];
        }
        return $this->formatResponse($data);
    }
}
