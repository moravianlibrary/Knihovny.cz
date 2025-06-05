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
            $this->resetValidRowIds();

            // Stop now if the user does not have valid catalog credentials available:
            if (!is_array($patron = $this->catalogLogin())) {
                return $patron;
            }

            // Connect to the ILS:
            $catalog = $this->getILS();

            // Check function config
            $functionConfig = $catalog->checkFunction(
                'getMyTransactionHistory',
                $patron
            );
            if (false === $functionConfig) {
                throw new \VuFind\Exception\ILS('ils_action_unavailable');
            }
            $purgeSelectedAllowed = !empty($functionConfig['purge_selected']);
            $purgeAllAllowed = !empty($functionConfig['purge_all']);

            // Get paging setup:
            $config = $this->getConfig();
            $pageOptions = $this->paginationHelper->getOptions(
                (int)$this->params()->fromQuery('page', 1),
                $this->params()->fromQuery('sort'),
                $config->Catalog->historic_loan_page_size ?? 50,
                $functionConfig
            );

            $sublibraries = [];
            if ($catalog->checkFunction('getMySublibraries', $patron) !== false) {
                $sublibraries = $catalog->getMySublibraries($patron);
            }
            $sublibrary = $this->params()->fromQuery('sublibrary', null);
            if ($sublibrary == null && count($sublibraries) > 1) {
                $sublibrary = array_key_first($sublibraries);
            }
            $ilsParams = $pageOptions['ilsParams'];
            $ilsParams['sublibrary'] = $sublibrary;

            // Get checked out item details:
            $result
                = $catalog->getMyTransactionHistory($patron, $ilsParams);

            if (isset($result['success']) && !$result['success']) {
                throw new \VuFind\Exception\ILS($result['status']);
            }

            $paginator = $this->paginationHelper->getPaginator(
                $pageOptions,
                $result['count'],
                $result['transactions']
            );
            if ($paginator) {
                $pageStart = $paginator->getAbsoluteItemNumber(1) - 1;
                $pageEnd = $paginator->getAbsoluteItemNumber($pageOptions['limit']) - 1;
            } else {
                $pageStart = 0;
                $pageEnd = $result['count'];
            }

            $driversNeeded = $hiddenTransactions = [];
            foreach ($result['transactions'] as $i => $current) {
                // Build record drivers (only for the current visible page):
                if ($pageOptions['ilsPaging'] || ($i >= $pageStart && $i <= $pageEnd)) {
                    $driversNeeded[] = $current;
                } else {
                    $hiddenTransactions[] = $current;
                }
                if ($purgeSelectedAllowed && isset($current['row_id'])) {
                    $this->rememberValidRowId($current['row_id']);
                }
            }

            $transactions = $this->ilsRecords()->getDrivers($driversNeeded);
            $this->addDetailsFromOfflineHoldings($transactions);
            // disable sorting
            $sortList = false;
            $params = $ilsParams;
            $params['cardId'] = $this->getCardId();
            $view = $this->createViewModel(
                compact(
                    'transactions',
                    'paginator',
                    'params',
                    'hiddenTransactions',
                    'sortList',
                    'functionConfig',
                    'purgeAllAllowed',
                    'purgeSelectedAllowed',
                    'sublibrary',
                    'sublibraries'
                )
            );
        } catch (\Exception $ex) {
            $view = $this->createViewModel();
            $view->error = true;
            $this->showException($ex);
        }
        if ($this->flashMessenger()->hasCurrentMessages('error')) {
            $view->error = true;
        }
        $view->setTemplate('checkouts/history-ajax');
        $view->setVariable('cardId', $this->getCardId());
        $result = $this->getViewRenderer()->render($view);
        return $this->getAjaxResponse('text/html', $result, null);
    }
}
