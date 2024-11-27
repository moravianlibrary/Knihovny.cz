<?php

namespace KnihovnyCz\Controller\Plugin;

use Laminas\Session\Container as SessionContainer;
use VuFind\Controller\Plugin\ResultScroller as Base;
use VuFind\Search\Memory as SearchMemory;
use VuFind\Search\Results\PluginManager as ResultsManager;
use VuFind\View\Helper\Root\Url as UrlHelper;

/**
 * Class for managing "next" and "previous" navigation within result sets.
 *
 * @category VuFind
 * @package  Controller_Plugins
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ResultScroller extends Base
{
    /**
     * Url helper
     *
     * @var UrlHelper
     */
    protected $urlHelper;

    /**
     * Constructor. Create a new search result scroller.
     *
     * @param SessionContainer $session   Session container
     * @param ResultsManager   $rm        Results manager
     * @param SearchMemory     $sm        Search memory
     * @param UrlHelper        $urlHelper Url helper
     * @param bool             $enabled   Is the scroller enabled?
     */
    public function __construct(
        SessionContainer $session,
        ResultsManager $rm,
        SearchMemory $sm,
        UrlHelper $urlHelper,
        bool $enabled = true
    ) {
        parent::__construct($session, $rm, $sm, $enabled);
        $this->urlHelper = $urlHelper;
    }

    /**
     * Get the previous/next record in the last search
     * result set relative to the current one, also return
     * the position of the current record in the result set.
     * Return array('previousRecord'=>previd, 'nextRecord'=>nextid,
     * 'currentPosition'=>number, 'resultTotal'=>number).
     *
     * @param \VuFind\RecordDriver\AbstractBase $driver Driver for the record
     * currently being displayed
     *
     * @return array
     */
    public function getScrollData($driver)
    {
        $retVal = [
            'firstRecord' => null, 'lastRecord' => null,
            'previousRecord' => null, 'nextRecord' => null,
            'currentPosition' => null, 'resultTotal' => null,
            'linkToResults' => null,
        ];

        // Process scroll data only if enabled and data exists:
        if (!$this->enabled || ($search = $this->restoreCurrentSearch()) == null) {
            return $retVal;
        }
        $this->data = $this->session->s[$search->getSearchId()] ?? null;
        if ($this->data == null) {
            // no data for scroller - return only link to results
            $retVal['linkToResults'] = $this->getLinkToResults($search);
            return $retVal;
        }
        $multiplied = $driver->tryMethod('isMultiplied', [], false);
        if (!$multiplied) {
            $driver = $driver->tryMethod(
                'getParentRecord',
                [],
                null
            ) ?? $driver;
        }
        // Get results:
        $result = $this->buildScrollDataArray($retVal, $driver, $search);
        // current page is updated after moving to previous or next page - must
        // be here after buildScrollDataArray to reflect it
        $result['linkToResults'] = $this->getLinkToResults($search, $result);
        $result['multiplied'] = $multiplied;
        // Touch and update session with any changes:
        $this->data->lastAccessTime = time();
        $this->session->s[$search->getSearchId()] = $this->data;
        return $result;
    }

    /**
     * Get link to page with results
     *
     * @param \VuFind\Search\Base\Results $search search
     * @param array|null                  $result result
     *
     * @return string|null link
     */
    protected function getLinkToResults(\VuFind\Search\Base\Results $search, ?array $result = null)
    {
        $action = $search->getOptions()->getSearchAction();
        $urlQuery = $search->getUrlQuery();
        $limit = null;
        $page = null;
        if (isset($result['currentPosition']) && $this->data != null) {
            if (isset($this->data->limit)) {
                $limit = $this->data->limit;
                $urlQuery = $urlQuery->setLimit($limit);
            }
            if (isset($this->data->sort)) {
                $urlQuery = $urlQuery->setSort($this->data->sort);
            }
            if (isset($this->data->page)) {
                $page = $this->data->page;
                $urlQuery = $urlQuery->setPage($page);
            }
        }
        $params = $urlQuery->getParams();
        $url = call_user_func($this->urlHelper, $action) . $params;
        if ($limit != null && $page != null && $result != null) {
            $offset = ($result['currentPosition'] - 1) - (($page - 1) * $limit);
            $url .= '#result' . $offset;
        }
        return $url;
    }

    /**
     * Return the last saved search.
     *
     * @return \VuFind\Search\Base\Results
     */
    protected function restoreCurrentSearch()
    {
        $searchId = $this->searchMemory->getCurrentSearchId();
        if ($searchId == null) {
            return null;
        }
        return parent::restoreSearch($searchId);
    }

    /**
     * Fetch the given page of results from the given search object and
     * return the IDs of the records in an array.
     *
     * @param object $searchObject The search object to use to execute the search
     * @param int    $page         The page number to fetch (null for current)
     *
     * @return array
     */
    protected function fetchPage($searchObject, $page = null)
    {
        if (null !== $page) {
            $searchObject->getParams()->setPage($page);
            $searchObject->performAndProcessSearch();
        }

        $retVal = [];
        foreach ($searchObject->getResults() as $record) {
            if (!($record instanceof \VuFind\RecordDriver\AbstractBase)) {
                return false;
            }
            $recordId = $record->getUniqueId();
            if (!$record->tryMethod('isMultiplied', [], false)) {
                $recordId = $record->tryMethod('getParentRecordID', [], $recordId);
            }
            $retVal[] = $record->getSourceIdentifier() . '|' . $recordId;
        }
        return $retVal;
    }
}
