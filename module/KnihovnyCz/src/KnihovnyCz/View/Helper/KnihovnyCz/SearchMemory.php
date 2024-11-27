<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use VuFind\View\Helper\Root\SearchMemory as Base;

/**
 * SearchMemory
 *
 * @category VuFind
 * @package  KnihovnyCz\View_Helpers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SearchMemory extends Base
{
    /**
     * Retrieve the parameters of the last search by the search class
     *
     * @param string $searchClassId Search class
     *
     * @return \VuFind\Search\Base\Params
     */
    public function getLastSearchParams($searchClassId)
    {
        // We don't use last search, so return empty parameters
        $paramsPlugin = $this->getView()->plugin('searchParams');
        return $paramsPlugin($searchClassId);
    }

    /**
     * Get the URL to edit the last search.
     *
     * @param string $searchClassId Search class
     * @param string $action        Action to take
     * @param mixed  $value         Value for action
     *
     * @return string
     */
    public function getEditLink($searchClassId, $action, $value)
    {
        $query = compact('searchClassId') + [$action => $value];
        $sid = $this->memory->getCurrentSearchId();
        if ($sid != null) {
            $query += ['sid' => $sid];
        }
        $url = $this->getView()->plugin('url');
        return $url('search-editmemory', [], compact('query'));
    }
}
