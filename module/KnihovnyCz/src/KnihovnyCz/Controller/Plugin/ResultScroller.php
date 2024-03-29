<?php

namespace KnihovnyCz\Controller\Plugin;

use Laminas\Config\Config;
use Laminas\Session\Container as SessionContainer;
use Laminas\View\Renderer\RendererInterface;
use VuFind\Controller\Plugin\ResultScroller as Base;
use VuFind\Search\Memory as SearchMemory;
use VuFind\Search\Results\PluginManager as ResultsManager;

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
     * Renderer
     *
     * @var RendererInterface
     */
    protected $renderer;

    protected $useParentRecord = true;

    /**
     * Constructor. Create a new search result scroller.
     *
     * @param SessionContainer  $session   Session container
     * @param ResultsManager    $rm        Results manager
     * @param SearchMemory      $sm        Search memory
     * @param RendererInterface $renderer  Renderer
     * @param Config            $searchCfg Search configuration
     * @param bool              $enabled   Is the scroller enabled?
     */
    public function __construct(
        SessionContainer $session,
        ResultsManager $rm,
        SearchMemory $sm,
        RendererInterface $renderer,
        \Laminas\Config\Config $searchCfg,
        $enabled = true
    ) {
        parent::__construct($session, $rm, $sm, $enabled);
        $dedupType = $searchCfg->Records->deduplication_type ?? '';
        $this->useParentRecord = ($dedupType != 'multiplying');
        $this->renderer = $renderer;
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
        if ($this->useParentRecord) {
            $driver = $driver->tryMethod('getParentRecord', [], null)
                ?? $driver;
        }
        $result = parent::getScrollData($driver);
        $result['linkToResults'] = null;
        if (
            isset($result['currentPosition'])
            && $result['currentPosition'] != null
        ) {
            $result['linkToResults'] = $this->getLinkToResults();
        }
        return $result;
    }

    /**
     * Get link to page with results
     *
     * @return string|null link
     */
    protected function getLinkToResults()
    {
        if (($search = $this->restoreLastSearch()) == null) {
            return null;
        }
        $action = $search->getOptions()->getSearchAction();
        $params = $search->getUrlQuery()->getParams();
        $urlHelper = $this->renderer->plugin('url');
        return $urlHelper($action) . $params;
    }

    /**
     * Return the last saved search.
     *
     * @return \VuFind\Search\Base\Results
     */
    protected function restoreLastSearch()
    {
        $searchId = $this->searchMemory->getLastSearchId();
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
            if ($this->useParentRecord) {
                $recordId = $record->tryMethod(
                    'getParentRecordID',
                    [],
                    $record->getUniqueId()
                );
            }
            $retVal[] = $record->getSourceIdentifier() . '|' . $recordId;
        }
        return $retVal;
    }
}
