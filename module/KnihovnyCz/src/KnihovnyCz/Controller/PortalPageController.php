<?php

namespace KnihovnyCz\Controller;

/**
 * Class PortalPageController
 *
 * @category VuFind
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class PortalPageController extends \VuFind\Controller\AbstractBase
{
    /**
     * Index action - redirects to legacy url
     *
     * @return \Laminas\Http\Response|\Laminas\View\Model\ViewModel
     */
    public function indexAction()
    {
        return $this->redirect()->toRoute(
            'content-page',
            $this->params()->fromRoute()
        );
    }
}
