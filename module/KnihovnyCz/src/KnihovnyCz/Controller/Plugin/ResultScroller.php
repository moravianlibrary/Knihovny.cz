<?php
/**
 * Class for managing "next" and "previous" navigation within result sets.
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
 * @category VuFind
 * @package  Controller_Plugins
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace KnihovnyCz\Controller\Plugin;

use Laminas\Session\Container as SessionContainer;
use Laminas\View\Renderer\RendererInterface;
use VuFind\Controller\Plugin\ResultScroller as Base;
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

    /**
     * Results
     *
     * @var \VuFind\Search\Base\Results|null
     */
    protected $lastSearch = null;

    /**
     * Constructor. Create a new search result scroller.
     *
     * @param SessionContainer  $session  Session container
     * @param ResultsManager    $rm       Results manager
     * @param RendererInterface $renderer Renderer
     * @param bool              $enabled  Is the scroller enabled?
     */
    public function __construct(
        SessionContainer $session,
        ResultsManager $rm,
        RendererInterface $renderer,
        $enabled = true
    ) {
        parent::__construct($session, $rm, $enabled);
        $this->renderer = $renderer;
    }

    /**
     * Initialize this result set scroller. This should only be called
     * prior to displaying the results of a new search.
     *
     * @param \VuFind\Search\Base\Results $searchObject The search object that was
     * used to execute the last search.
     *
     * @return bool
     */
    public function init($searchObject)
    {
        $this->lastSearch = null;
        return parent::init($searchObject);
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
        $this->initLastSearch();
        $driver = $driver->tryMethod('getParentRecord', [], null)
            ?? $driver;
        $result = parent::getScrollData($driver);
        $result['linkToResults'] = null;
        if (isset($result['currentPosition'])
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
        if (($search = parent::restoreLastSearch()) == null) {
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
        return $this->lastSearch;
    }

    /**
     * Initialize the last saved search. Optimization to prevent restoring
     * last search twice from this and parent class.
     *
     * @return void
     */
    protected function initLastSearch()
    {
        if ($this->enabled) {
            $this->lastSearch = parent::restoreLastSearch();
        }
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
            $retVal[] = $record->getSourceIdentifier() . '|' . $record->tryMethod(
                'getParentRecordID',
                [],
                $record->getUniqueId()
            );
        }
        return $retVal;
    }
}
