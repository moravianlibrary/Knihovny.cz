<?php

/**
 * Class Inspiration
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
 * @package  KnihovnyCz\Controllers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

use Laminas\Db\Sql\Predicate\Like as LikePredicate;
use Laminas\Db\Sql\Select;

/**
 * Class Inspiration
 *
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InspirationController extends \VuFind\Controller\AbstractBase
{
    /**
     * Home action
     *
     * @return \Laminas\View\Model\ViewModel
     */
    public function homeAction()
    {
        $blocks = $this->serviceLocator->get(\VuFind\ContentBlock\BlockLoader::class)
            ->getFromConfig('content', 'Inspiration', 'content_block');
        $tableManager = $this->serviceLocator
            ->get(\VuFind\Db\Table\PluginManager::class);
        $widgetsTable = $tableManager->get(\KnihovnyCz\Db\Table\Widget::class);
        $widgetList = $widgetsTable->select();

        $blockManager = $this->serviceLocator
            ->get(\VuFind\ContentBlock\PluginManager::class);
        $listType = 'Inspiration';
        $widgets = [];
        foreach ($widgetList as $widget) {
            $contentBlock = $blockManager->get($listType);
            $contentBlock->setConfig($widget->name . ':0');
            $widgets[$widget->category][] = $contentBlock->getContext();
        }
        $userListTable = $tableManager->get(\VuFind\Db\Table\UserList::class);
        $userLists = $userListTable->select(
            function (Select $select) {
                $select->join('user', 'user.id = user_list.user_id', [])
                    ->where(
                        [
                            'user_list.public' => 1,
                            new LikePredicate('user.major', '%widgets%')
                        ]
                    );
            }
        );
        $listType = 'UserList';
        foreach ($userLists as $userList) {
            $contentBlock = $blockManager->get($listType);
            $contentBlock->setConfig($userList->id . ':0');
            $widgets[$userList->category][] = $contentBlock->getContext();
        }
        $sorter = $this->serviceLocator->get(\VuFind\I18n\Sorter::class);
        foreach ($widgets as $category => $widget) {
            usort(
                $widgets[$category],
                function ($a, $b) use ($sorter) {
                    $aTitle = $a['list']->title_cs ?? $a['list']->title ?? '';
                    $bTitle = $b['list']->title_cs ?? $b['list']->title ?? '';
                    return $sorter->compare($aTitle, $bTitle);
                }
            );
        }
        uasort(
            $widgets,
            function ($a, $b) {
                return -1 * (count($a) - count($b));
            }
        );

        return $this->createViewModel(compact('blocks', 'widgets'));
    }

    /**
     * Show action
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response
     */
    public function homeLegacyAction()
    {
        return $this->redirect()->toRoute('inspiration');
    }

    /**
     * Show action
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response
     */
    public function showAction()
    {
        $list = $this->params()->fromRoute('list');
        $listData = $this->getListData($list);
        return $this->createViewModel($listData);
    }

    /**
     * Get inspiration list data
     *
     * @param string $listId List identifier
     *
     * @return array
     */
    protected function getListData(string $listId): array
    {
        $blockManager = $this->serviceLocator
            ->get(\VuFind\ContentBlock\PluginManager::class);
        $listType = 'Inspiration';
        $slugParts = explode('-', $listId);
        if (is_numeric($slugParts[0])) {
            $listId = $slugParts[0];
            $listType = 'UserList';
        }
        $contentBlock = $blockManager->get($listType);
        $contentBlock->setConfig($listId . ':50');
        return $contentBlock->getContext();
    }
}
