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
        $blockManager = $this->serviceLocator
            ->get(\VuFind\ContentBlock\PluginManager::class);

        $widgets = [];
        $userListTable = $tableManager->get(\VuFind\Db\Table\UserList::class);
        $userLists = $userListTable->getInspirationLists();

        $listType = 'UserList';
        foreach ($userLists as $userList) {
            $contentBlock = $blockManager->get($listType);
            $contentBlock->setConfig($userList->id . ':0');
            $widgets[$userList->category][] = $contentBlock->getContext();
        }

        $sorter = $this->serviceLocator->get(\VuFind\I18n\Sorter::class);
        foreach (array_keys($widgets) as $category) {
            usort(
                $widgets[$category],
                function ($first, $second) use ($sorter) {
                    return $sorter->compare(
                        $first['list']->title ?? '',
                        $second['list']->title ?? ''
                    );
                }
            );
        }
        uasort(
            $widgets,
            function ($first, $second) {
                return count($second) - count($first);
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
        if (empty($list)) {
            return $this->redirect()->toRoute('inspiration');
        }
        $listData = $this->getListData($list);
        if (empty($listData)) {
            $this->getResponse()->setStatusCode(404);
            return $this->createViewModel();
        }
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
        [$userListId, ] = explode('-', $listId);
        $listType = 'UserList';
        if (!is_numeric($userListId)) {
            $tableManager
                = $this->serviceLocator->get(\VuFind\Db\Table\PluginManager::class);
            $listTable = $tableManager->get('UserList');
            $listRow = $listTable->select(['old_name' => $listId])->current();
            if (empty($listRow)) {
                return [];
            }
            $listId = $listRow->id;
        }
        $contentBlock = $blockManager->get($listType);
        $contentBlock->setConfig($listId . ':500');
        if (!$contentBlock->validateSlug($listId)) {
            $this->redirect()->toUrl($contentBlock->getListUrl());
        }
        return $contentBlock->getContext();
    }
}
