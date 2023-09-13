<?php

/**
 * Class CartController
 *
 * PHP version 7
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
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\Stdlib\RequestInterface as Request;

use function is_array;
use function strlen;

/**
 * Class CartController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class CartController extends \VuFind\Controller\CartController
{
    /**
     * Figure out an action from the request....
     *
     * @param string $default Default action if none can be determined.
     *
     * @return string
     */
    protected function getCartActionFromRequest($default = 'Home')
    {
        if (strlen($this->params()->fromPost('cite', '')) > 0) {
            return 'Cite';
        }
        return parent::getCartActionFromRequest($default);
    }

    /**
     * Display cart contents.
     *
     * @return mixed
     */
    public function citeAction()
    {
        // Retrieve ID list:
        $ids = null === $this->params()->fromPost('selectAll')
            ? $this->params()->fromPost('ids')
            : $this->params()->fromPost('idsAll');
        // Retrieve follow-up information if necessary:
        if (!is_array($ids) || empty($ids)) {
            $ids = $this->followup()->retrieveAndClear('cartIds');
        }
        if (!is_array($ids) || empty($ids)) {
            return $this->redirectToSource('error', 'bulk_noitems_advice');
        }
        $citacePro = $this->serviceLocator->get(
            \KnihovnyCz\Service\CitaceProService::class
        );
        $view = $this->createViewModel();
        $view->citationStyles = $citacePro->getCitationStyles();
        $view->currentStyle = $citacePro->getDefaultCitationStyle();
        $citations = [];
        $ids = null === $this->params()->fromPost('selectAll')
            ? $this->params()->fromPost('ids', [])
            : $this->params()->fromPost('idsAll', []);
        foreach ($ids as $id) {
            try {
                $citations[$id] = $citacePro->getCitation($id);
            } catch (\Exception $ex) {
                $citations[$id] = false;
            }
        }
        $view->citations = $citations;
        $view->setTemplate('cart/cite');
        return $view;
    }

    /**
     * Process bulk actions from the MyResearch area; most of this is only necessary
     * when Javascript is disabled.
     *
     * @return mixed
     */
    public function myresearchbulkAction()
    {
        // We came in from the MyResearch section -- let's remember which list (if
        // any) we came from so we can redirect there when we're done:
        $listID = $this->params()->fromPost('listID');
        $this->session->url = empty($listID)
            ? $this->url()->fromRoute('myresearch-favorites')
            : $this->url()->fromRoute('userList', ['id' => $listID]);

        // Now forward to the requested controller/action:
        $controller = 'Cart';   // assume Cart unless overridden below.
        if (strlen($this->params()->fromPost('email', '')) > 0) {
            $action = 'Email';
        } elseif (strlen($this->params()->fromPost('print', '')) > 0) {
            $action = 'PrintCart';
        } elseif (strlen($this->params()->fromPost('delete', '')) > 0) {
            $controller = 'MyResearch';
            $action = 'Delete';
        } elseif (strlen($this->params()->fromPost('add', '')) > 0) {
            $action = 'Home';
        } elseif (strlen($this->params()->fromPost('export', '')) > 0) {
            $action = 'Export';
        } elseif (strlen($this->params()->fromPost('cite', '')) > 0) {
            $action = 'Cite';
        } else {
            $action = $this->followup()->retrieveAndClear('cartAction', null);
            if (empty($action)) {
                throw new \Exception('Unrecognized bulk action.');
            }
        }
        return $this->forwardTo($controller, $action);
    }
}
