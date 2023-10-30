<?php

namespace KnihovnyCz\Recommend;

use VuFind\Recommend\RecommendInterface;

/**
 * RecommendOtherPortals Recommendations Module
 *
 * This class recommends links to main portal.
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class RecommendOtherPortals implements RecommendInterface
{
    /**
     * Configuration loader
     *
     * @var \VuFind\Config\PluginManager
     */
    protected $configLoader;

    /*
     * Base URL on which main portal is running.
     *
     */
    protected $links = [];

    /**
     * Link to main portal.
     *
     * @var false|string
     */
    protected $query = false;

    /**
     * Constructor
     *
     * @param \VuFind\Config\PluginManager $configLoader Configuration loader
     */
    public function __construct(\VuFind\Config\PluginManager $configLoader)
    {
        $this->configLoader = $configLoader;
    }

    /**
     * Store the configuration of the recommendation module.
     *
     * RecommendOtherPortals:[ini section]:[ini name]
     *     Display a list of recommended links, taken from [ini section] in
     *     [ini name], where the section is a mapping of label => URL. [ini name]
     *     defaults to searches.ini, and [ini section] defaults to RecommendLinks.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        $settings = explode(':', $settings);
        $mainSection = empty($settings[0]) ? 'OtherPortals' : $settings[0];
        $iniName = $settings[1] ?? 'searches';
        $config = $this->configLoader->get($iniName);
        $this->links = $config->$mainSection ?? [];
    }

    /**
     * Called before the Search Results object performs its main search
     * (specifically, in response to \VuFind\Search\SearchRunner::EVENT_CONFIGURED).
     * This method is responsible for setting search parameters needed by the
     * recommendation module and for reading any existing search parameters that may
     * be needed.
     *
     * @param \VuFind\Search\Base\Params $params  Search parameter object
     * @param \Laminas\Stdlib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    public function init($params, $request)
    {
        // No action needed.
    }

    /**
     * Called after the Search Results object has performed its main search.  This
     * may be used to extract necessary information from the Search Results object
     * or to perform completely unrelated processing.
     *
     * @param \VuFind\Search\Base\Results $results Search results object
     *
     * @return void
     */
    public function process($results)
    {
        $this->query = $results->getUrlQuery();
    }

    /**
     * Get link to main portal
     *
     * @return false|string
     */
    public function getLinks()
    {
        if (empty($this->links) || !$this->query) {
            return [];
        }
        $links = [];
        foreach ($this->links as $portal => $baseUrl) {
            $links[$portal] = rtrim($baseUrl, '/') . '/Search/Results'
                . $this->query;
        }
        return $links;
    }
}
