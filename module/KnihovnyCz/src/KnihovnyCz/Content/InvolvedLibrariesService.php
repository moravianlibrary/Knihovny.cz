<?php

declare(strict_types=1);

namespace KnihovnyCz\Content;

use Laminas\Stdlib\Parameters;
use VuFind\Search\Results\PluginManager as ResultsManager;

/**
 * Class InvolvedLibrariesService
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Content
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InvolvedLibrariesService implements \VuFind\I18n\HasSorterInterface
{
    use \VuFind\I18n\HasSorterTrait;

    /**
     * Results manager
     *
     * @var ResultsManager
     */
    protected ResultsManager $resultsManager;

    protected array $libraries;

    /**
     * Constructor
     *
     * @param ResultsManager $resultsManager Results manager
     */
    public function __construct(ResultsManager $resultsManager)
    {
        $this->resultsManager = $resultsManager;
    }

    /**
     * Get libraries involved in project Knihovny.cz
     *
     * @return array
     */
    public function getInvolvedLibraries(): array
    {
        if (!isset($this->libraries)) {
            $this->libraries = $this->searchInvolvedLibraries();
        }
        return $this->libraries;
    }

    /**
     * Get number of libraries involved in project Knihovny.cz
     *
     * @return int
     */
    public function getInvolvedLibrariesCount(): int
    {
        $libraries = $this->getInvolvedLibraries();
        $count = 0;
        foreach ($libraries as $region) {
            $count += count($region);
        }
        return $count;
    }

    /**
     * Search for involved libraries in solr index
     *
     * @return array
     *
     * @throws \VuFind\Exception\BadConfig
     */
    protected function searchInvolvedLibraries(): array
    {
        $filters = ['portal_facet_mv:"KNIHOVNYCZ_YES"'];
        /**
         * Search results
         *
         * @var \VuFind\Search\Search2\Results $results
         */
        $results = $this->resultsManager->get('Search2');
        $params = $results->getParams();
        $params->getOptions()->disableHighlighting();
        $params->getOptions()->spellcheckEnabled(false);
        $params->getOptions()->setLimitOptions([1000]);
        $params->initFromRequest(new Parameters(['filter' => $filters]));
        $libraries = [];
        foreach ($results->getResults() as $library) {
            $name = $library->getTranslatedNameBySource();
            $region = $library->getRegion();
            $sourceId = $library->getCpkCode();
            if ($name !== '' && !isset($libraries[$region][$sourceId])) {
                $libraries[$region][$sourceId] = [
                    'name' => $name,
                    'id' => $library->getUniqueID(),
                ];
            }
        }
        foreach ($libraries as &$librariesByRegion) {
            usort(
                $librariesByRegion,
                function ($a, $b) {
                    return $this->getSorter()->compare($a['name'], $b['name']);
                }
            );
        }
        uksort($libraries, [$this->getSorter(), 'compare']);
        return $libraries;
    }
}
