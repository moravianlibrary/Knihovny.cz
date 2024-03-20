<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\Url;
use VuFind\Search\Base\Results;
use VuFind\Search\Memory;
use VuFind\Search\Results\PluginManager;
use VuFind\Search\SearchTabsHelper;
use VuFind\View\Helper\Root\SearchTabs as Base;

/**
 * Class SearchTabs
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SearchTabs extends Base
{
    /**
     * Constructor
     *
     * @param PluginManager    $results Search results plugin manager
     * @param Url              $url     URL helper
     * @param SearchTabsHelper $helper  Search tabs helper
     * @param Memory           $memory  Search memory
     */
    public function __construct(
        PluginManager $results,
        Url $url,
        SearchTabsHelper $helper,
        protected Memory $memory
    ) {
        parent::__construct($results, $url, $helper);
    }

    /**
     * Get an url to "search home".
     *
     * @param string $class   Search class ID
     * @param array  $filters Tab filters
     *
     * @return string
     */
    protected function getHomeTabUrl($class, $filters)
    {
        // If an advanced search is available, link there; otherwise, just go
        // to the search home:
        $results = $this->results->get($class);
        $url = ($this->url)($results->getOptions()->getSearchAction())
            . $this->buildUrlHiddenFilters($results, $filters);
        return $this->appendStoredSettings($class, $url);
    }

    /**
     * Append stored settings to search URL
     *
     * @param string $class Search class ID
     * @param string $url   Target search URL
     *
     * @return string
     */
    protected function appendStoredSettings($class, $url)
    {
        $lastLimit = $this->memory->retrieveLastSetting($class, 'limit');
        if ($lastLimit != null) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'limit=' . $lastLimit;
        }
        return $url;
    }
}
