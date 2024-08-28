<?php

namespace KnihovnyCz\Recommend;

use VuFind\Recommend\SideFacetsDeferred as Base;

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
    protected $institutionField;

    /**
     * Auth Manager
     *
     * @var \VuFind\Auth\Manager
     */
    protected $authManager;

    /**
     * Constructor
     *
     * @param \VuFind\Config\PluginManager $configLoader Configuration loader
     * @param HierarchicalFacetHelper      $facetHelper  Helper for handling
     * hierarchical facets
     * @param \VuFind\Auth\Manager         $authManager  Auth manager
     */
    public function __construct(
        \VuFind\Config\PluginManager $configLoader,
        \VuFind\Search\Solr\HierarchicalFacetHelper $facetHelper = null,
        \VuFind\Auth\Manager $authManager
    ) {
        parent::__construct($configLoader, $facetHelper);
        $this->authManager = $authManager;
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
        $institutions = $this->getMyInstitutions();
        if (empty($institutions)) {
            return false;
        }
        $url = $this->getResults()->getUrlQuery()
            ->removeFilterByField($this->getInstitutionField());
        foreach ($this->getMyInstitutions() as $filterValue) {
            $url = $url->addFacet($this->institutionField, $filterValue, 'OR');
        }
        return $url;
    }

    /**
     * Get values for my institutions filter
     *
     * @return array
     */
    protected function getMyInstitutions()
    {
        /**
         * User model
         *
         * @var \KnihovnyCz\Db\Row\User|false $user
         */
        $user = $this->authManager->getUserObject();
        if ($user == null) {
            return [];
        }
        $savedInstitutions = $user->getUserSettings()->getSavedInstitutions();
        if (!empty($savedInstitutions)) {
            return $savedInstitutions;
        }
        $prefixes = $user->getLibraryPrefixes();
        $filters = [];
        $facetConfig = $this->configLoader->get('facets');
        if (!isset($facetConfig->InstitutionsMappings)) {
            return [];
        }
        foreach ($facetConfig->InstitutionsMappings as $source => $filter) {
            if (in_array($source, $prefixes)) {
                $filters[] = $filter;
            }
        }
        return $filters;
    }
}
