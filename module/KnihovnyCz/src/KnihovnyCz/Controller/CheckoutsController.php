<?php

namespace KnihovnyCz\Controller;

/**
 * Class CheckoutsController
 *
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 *
 * @method Plugin\FlashRedirect flashRedirect() Flash redirect controller plugin
 */
class CheckoutsController extends \VuFind\Controller\CheckoutsController
{
    use \VuFind\Controller\AjaxResponseTrait;
    use \KnihovnyCz\Controller\CatalogLoginTrait;
    use MyResearchTrait;

    /**
     * Send list of historic loans to view
     *
     * @return mixed
     */
    public function historyAction()
    {
        // Force login:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }
        $view = $this->createViewModel();
        $view->setTemplate('checkouts/history-all');
        return $view;
    }

    /**
     * Send list of historic loans to view
     *
     * @return mixed
     */
    public function historyAjaxAction()
    {
        try {
            $this->flashRedirect()->restore();
            $view = parent::historyAction();
            // disable sorting
            $view->sortList = false;
            $transactions = $view->transactions ?? [];
            $this->addDetailsFromOfflineHoldings($transactions);
        } catch (\Exception $ex) {
            $view = $this->createViewModel();
            $view->error = true;
            $this->showException($ex);
        }
        if (!($view instanceof \Laminas\View\Model\ViewModel)) {
            $view = $this->createViewModel(
                [
                    'error' => 'ils_offline_home_message',
                ]
            );
        }
        if ($this->flashMessenger()->hasCurrentMessages('error')) {
            $view->error = true;
        }
        $view->setTemplate('checkouts/history-ajax');
        $view->setVariable('cardId', $this->getCardId());
        $params = $view->getVariable('params', []);
        $params['cardId'] = $this->getCardId();
        $view->setVariable('params', $params);
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
    }
}
