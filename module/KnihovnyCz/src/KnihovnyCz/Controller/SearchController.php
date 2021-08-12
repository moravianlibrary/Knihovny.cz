<?php
declare(strict_types=1);

/**
 * Class SearchController
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
 * @category CPK-vufind-6
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Stdlib\ResponseInterface as Response;

/**
 * Class SearchController
 *
 * @category CPK-vufind-6
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SearchController extends \VuFind\Controller\SearchController
{
    /**
     * Dispatch a request
     *
     * @param Request       $request  Http request
     * @param null|Response $response Http response
     *
     * @return Response|mixed
     */
    public function dispatch(Request $request, Response $response = null)
    {
        $type = $this->params()->fromQuery('type0');
        if (is_array($type) ? in_array('Libraries', $type) : $type == 'Libraries') {
            $type = 'AllLibraries';
            $lookfor = $this->params()->fromQuery('lookfor0');
            $lookfor = is_array($lookfor) ? $lookfor[0] : $lookfor;
            $limit = 20;
            return $this->redirect()->toRoute(
                'search2-results', [],
                ['query' => compact('type', 'lookfor', 'limit')]
            );
        }
        return parent::dispatch($request, $response);
    }
}
