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
     * Search Memory
     *
     * @var Memory
     */
    protected $memory;

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
        Memory $memory
    ) {
        parent::__construct($results, $url, $helper);
        $this->memory = $memory;
    }

    /**
     * Create information representing a tab linking to "search home."
     *
     * @param string $id             Tab ID
     * @param string $class          Search class ID
     * @param string $label          Display text for tab
     * @param array  $filters        Tab filters
     * @param string $permissionName Name of a permissionrule
     *
     * @return array
     */
    protected function createHomeTab($id, $class, $label, $filters, $permissionName)
    {
        $results = $this->results->get($class);
        $url = ($this->url)($results->getOptions()->getSearchAction())
            . $this->buildUrlHiddenFilters($results, $filters);
        return [
            'id' => $id,
            'class' => $class,
            'label' => $label,
            'permission' => $permissionName,
            'selected' => false,
            'url' => $this->appendStoredSettings($class, $url),
        ];
    }

    /**
     * Create information representing a basic search tab.
     *
     * @param string $id             Tab ID
     * @param string $class          Search class ID
     * @param string $label          Display text for tab
     * @param string $newUrl         Target search URL
     * @param string $permissionName Name of a permissionrule
     *
     * @return array
     */
    protected function createBasicTab($id, $class, $label, $newUrl, $permissionName)
    {
        return [
            'id' => $id,
            'class' => $class,
            'label' => $label,
            'permission' => $permissionName,
            'selected' => false,
            'url' => $this->appendStoredSettings($class, $newUrl),
        ];
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
