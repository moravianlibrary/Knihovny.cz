<?php

/**
 * Class EmbeddedSearchController
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
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\Stdlib\ResponseInterface as Response;
use Laminas\View\Model\ViewModel;

/**
 * Class EmbeddedSearchController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
final class EmbeddedSearchController extends EmbeddedController
{
    /**
     * Show embedded search for use in HTML iframe
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Stdlib\ResponseInterface
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function indexAction(): ViewModel|Response
    {
        $this->setLayout();

        $view = $this->createViewModel();
        $view->setTemplate('embedded/search');

        // Use separate parameter for language so we are not interfering with
        // language for portal
        $lang = $this->params()->fromQuery('lang');
        if ($lang != null) {
            $this->setLanguage($lang);
        }

        $view->setVariable('lookfor', $this->params()->fromQuery('lookfor', ''));

        $config = $this->getConfig("config");
        $databases = [
            'default' => [
                'url' => '/Search/Results',
                'type' => 'AllFields',
            ],
            'eds' => [
                'url' => '/Summon/Search',
                'type' => 'AllFields',
            ],
            'libraries' => [
                'url' => '/Libraries/Results',
                'type' => 'AllLibraries',
            ],
        ];
        $database = strtolower($this->params()->fromQuery('database', ''));
        $search = $databases[$database] ?? $databases['default'];
        $router = $this->serviceLocator->get('HttpRouter');
        $serverUrl = $this->serviceLocator->get('ViewRenderer')->plugin('serverurl');
        $baseUrl = $serverUrl($router->assemble([], ['name' => 'home']));

        $view->setVariables(
            [
                'link' => rtrim($baseUrl, '/') . $search['url'],
                'type' => $search['type'],
                'baseUrl' => $baseUrl,
                'title' => $config->Embedded->title ?? 'logo_title',
                'position' => $this->params()->fromQuery('position', 'left'),
                'language' => $lang,
            ]
        );
        return $view;
    }
}
