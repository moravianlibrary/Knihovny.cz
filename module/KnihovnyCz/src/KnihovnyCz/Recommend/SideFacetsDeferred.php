<?php

namespace KnihovnyCz\Recommend;

use KnihovnyCz\Service\PreferredInstitutionsService;
use VuFind\Recommend\SideFacetsDeferred as Base;
use VuFind\Search\Solr\HierarchicalFacetHelper;

/**
 * SideFacetsDeferred Recommendations Module
 *
 * This class provides recommendations displaying facets beside search results
 * after the search results have been displayed
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class SideFacetsDeferred extends Base
{
    /**
     * Solr institution field
     *
     * @var string
     */
    protected string $institutionField;

    /**
     * Constructor
     *
     * @param \VuFind\Config\PluginManager  $configLoader                 Configuration loader
     * @param ?HierarchicalFacetHelper      $facetHelper                  Helper for handling hierarchical facets
     * @param ?\VuFind\Auth\Manager         $authManager                  Auth manager
     * @param ?PreferredInstitutionsService $preferredInstitutionsService Preferred institutions service
     */
    public function __construct(
        \VuFind\Config\PluginManager $configLoader,
        ?HierarchicalFacetHelper $facetHelper = null,
        protected readonly ?\VuFind\Auth\Manager $authManager = null,
        protected readonly ?PreferredInstitutionsService $preferredInstitutionsService = null
    ) {
        parent::__construct($configLoader, $facetHelper);
    }

    /**
     * Store the configuration of the recommendation module.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        parent::setConfig($settings);
        $config = $this->configLoader->get('searches');
        $this->institutionField = $config->Records->institution_field
            ?? 'region_institution_facet_mv';
    }

    /**
     * Get Solr institution filed
     *
     * @return string
     */
    public function getInstitutionField()
    {
        return $this->institutionField;
    }

    /**
     * Get selected institution filter
     *
     * @return array
     */
    public function getSelectedInstitutionFilter()
    {
        $filter = $this->getResults()->getParams()->getFilterList();
        $institutions = [];
        foreach ($filter['Institution'] ?? [] as $institution) {
            $institutions[] = $institution['value'];
        }
        return $institutions;
    }

    /**
     * Get filter with my institutions
     *
     * @return false|\VuFind\Search\UrlQueryHelper
     */
    public function getMyFilter()
    {
        $institutions = $this->preferredInstitutionsService->getFiltersFromUser();
        if (empty($institutions)) {
            return false;
        }
        $url = $this->getResults()->getUrlQuery()
            ->removeFilterByField($this->getInstitutionField());
        foreach ($institutions as $filterValue) {
            $url = $url->addFacet($this->institutionField, $filterValue, 'OR');
        }
        return $url;
    }
}
