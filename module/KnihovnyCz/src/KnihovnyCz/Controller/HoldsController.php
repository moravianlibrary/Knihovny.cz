<?php

namespace KnihovnyCz\Controller;

use Laminas\View\Model\ViewModel;
use VuFind\Controller\HoldsController as HoldsControllerBase;

/**
 * Class HoldsController
 *
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 *
 * @method Plugin\FlashRedirect flashRedirect() Flash redirect controller plugin
 */
class HoldsController extends HoldsControllerBase
{
    use \VuFind\Controller\AjaxResponseTrait;
    use \KnihovnyCz\Controller\CatalogLoginTrait;

    use \KnihovnyCz\Controller\MyResearchTrait;

    /**
     * Send list of holds to view
     *
     * @return mixed
     */
    public function listAction()
    {
        // Force login:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }
        $view = $this->createViewModel();
        $view->setTemplate('holds/list-all');
        return $view;
    }

    /**
     * Send list of holds to view
     *
     * @return mixed
     */
    public function listAjaxAction()
    {
        $this->flashRedirect()->restore();
        $view = null;
        try {
            $view = parent::listAction();
        } catch (\Exception $ex) {
            $this->showException($ex);
        }
        $error = ($view == null || !($view instanceof ViewModel));
        if (!$error) {
            $recordList = $view->recordList ?? [];
            $this->addDetailsFromOfflineHoldings($recordList);
        }
        // active operation failed -> redirect to show checked out items
        if ($this->getRequest()->isPost() && $error) {
            $url = $this->url()->fromRoute('holds-listajax');
            return $this->flashRedirect()->toUrl(
                $url . '?cardId='
                . $this->getCardId()
            );
        }
        if ($view == null) {
            $view = new ViewModel();
            $view->error = $error;
        }
        $view->setTemplate('holds/list-ajax');
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
    }
}
