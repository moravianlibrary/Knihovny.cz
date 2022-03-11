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
use Laminas\Db\Sql\Select;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\Db\Table\UserList;
use VuFind\Db\Table\UserResource;

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
     * User list table
     *
     * @var UserList
     */
    protected UserList $userList;

    /**
     * User resource table
     *
     * @var UserResource
     */
    protected UserResource $resource;

    /**
     * Constructor
     *
     * @param Widget        $widgets        Widget table
     * @param WidgetContent $widgetContents Widget content table
     * @param UserList      $userLists      User lists table
     * @param UserResource  $resource       User resource table
     */
    public function __construct(
        Widget $widgets,
        WidgetContent $widgetContents,
        UserList $userLists,
        UserResource $resource
    ) {
        $this->widgets = $widgets;
        $this->content = $widgetContents;
        $this->userList = $userLists;
        $this->resource = $resource;
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
        $data = [];

        /* Inspiration lists from widget tables */
        $widgets = $this->widgets->select();
        foreach ($widgets as $widget) {
            $contents = $this->content->select(['widget_id' => $widget['id']]);
            $data[] = [
                'list' => $widget['name'],
                'items' => array_column($contents->toArray(), 'value'),
            ];
        }

        /* Inspiration lists from user defined lists */
        $lists = $this->userList->select(['public' => 1]);
        foreach ($lists as $list) {
            $listId = $list['id'];
            $resources = $this->resource->select(
                function (Select $select) use ($listId) {
                    $select->where->equalTo('list_id', $listId);
                    $select->columns(['id']);
                    $select->join(
                        'resource',
                        'resource.id = user_resource.resource_id',
                        ['record_id']
                    );
                }
            );

            $data[] = [
                'list' => $list->getSlug(),
                'items' => array_column($resources->toArray(), 'record_id'),
            ];
        }
        return $this->formatResponse($data);
    }
}
