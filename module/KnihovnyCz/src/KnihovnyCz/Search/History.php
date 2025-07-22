<?php

namespace KnihovnyCz\Search;

/**
 * Class History
 *
 * @category VuFind
 * @package  Search
 * @author   Pavel Pátek <pavel.patek@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class History extends \VuFind\Search\History
{
    /**
     * Get the user's saved and temporary search histories.
     *
     * @param int $userId User ID (null if logged out)
     *
     * @return array
     * @throws \Exception
     */
    public function getSearchHistory($userId = null)
    {
        // Retrieve search history
        $searchHistory = $this->searchService->getSearches($this->sessionId, $userId);

        // Loop through and sort the history
        $saved = $schedule = $unsaved = [];
        foreach ($searchHistory as $current) {
            try {
                $search = $current->getSearchObject()?->deminify($this->resultsManager);
            } catch (\Throwable $e) {
                $search = $this->resultsManager->get('emptyset');
            }
            if (!$search) {
                throw new \Exception("Problem getting search object from search {$current->getId()}.");
            }
            if ($current->getSaved()) {
                $saved[] = $search;
            } else {
                $unsaved[] = $search;
            }
            if ($search->getOptions()->supportsScheduledSearch()) {
                $schedule[$current->getId()] = $current->getNotificationFrequency();
            }
        }

        return compact('saved', 'schedule', 'unsaved');
    }
}
