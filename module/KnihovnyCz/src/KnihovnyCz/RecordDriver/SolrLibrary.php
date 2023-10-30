<?php

namespace KnihovnyCz\RecordDriver;

use KnihovnyCz\RecordDriver\Feature\WikidataTrait;
use KnihovnyCz\Wikidata\WheelchairAccessibility;

/**
 * Knihovny.cz solr library record driver
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class SolrLibrary extends \KnihovnyCz\RecordDriver\SolrMarc
{
    use WikidataTrait;

    protected array $wikiExternalLinks = [
        'isds' => 'P8987',
        'facebook' => 'P2013',
        'youtube' => 'P2397',
        'github' => 'P2037',
        'twitter' => 'P2002',
        'instagram' => 'P2003',
        'orgcode' => 'P3234',
    ];

    protected array $socialSites = [
        'facebook',
        'instagram',
        'twitter',
        'youtube',
        'github',
    ];

    /**
     * Facets configuration
     *
     * @var \Laminas\Config\Config
     */
    protected $facetsConfig = null;

    /**
     * Institution field
     *
     * @var string
     */
    protected $institutionField = null;

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config $mainConfig     VuFind main configuration (omit
     * for built-in defaults)
     * @param \Laminas\Config\Config $recordConfig   Record-specific configuration
     * file (omit to use $mainConfig as $recordConfig)
     * @param \Laminas\Config\Config $searchSettings Search-specific configuration
     * file
     */
    public function __construct(
        $mainConfig = null,
        $recordConfig = null,
        $searchSettings = null
    ) {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);
        $this->institutionField = $searchSettings->Records->institution_field ??
            'region_institution_facet_mv';
    }

    /**
     * Get library name
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->fields['name_display'] ?? '';
    }

    /**
     * Get library alternative name
     *
     * @return array
     */
    public function getAlternativeTitles()
    {
        return $this->fields['name_alt_display_mv'] ?? [];
    }

    /**
     * Get town or city
     *
     * @return string
     */
    public function getTown()
    {
        return $this->fields['town_display'] ?? '';
    }

    /**
     * Get library opening hours
     *
     * @return array
     */
    public function getLibraryHours()
    {
        $result = [];
        $hours = $this->fields['hours_display'] ?? '';
        if (!empty($hours)) {
            $days = explode('|', $hours);
            foreach ($days as $day) {
                $parts = explode(' ', trim($day), 2);
                $result[$parts[0]] = $parts[1];
            }
        }
        return $result;
    }

    /**
     * Get last updated metadata date
     *
     * @return String
     */
    public function getLastUpdated()
    {
        return $this->fields['lastupdated_display'] ?? '';
    }

    /**
     * Get library postal addresses
     *
     * @return array
     */
    public function getLibraryAddress()
    {
        return $this->fields['address_display_mv'] ?? [];
    }

    /**
     * Get an array of library ico and dicn
     *
     * @return string
     */
    public function getIco()
    {
        return $this->fields['ico_display'] ?? '';
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getLibNote()
    {
        return $this->fields['note_display'] ?? '';
    }

    /**
     * Get secondary notes
     *
     * @return string
     */
    public function getLibNote2()
    {
        return $this->fields['note2_display'] ?? '';
    }

    /**
     * Get sigla identifier
     *
     * @return string
     */
    public function getSigla()
    {
        return $this->fields['sigla_display'] ?? '';
    }

    /**
     * Get related URLs
     *
     * @return array
     */
    public function getWebsites()
    {
        $urls = $this->fields['url_display_mv'] ?? [];
        $filter = function ($url) {
            $parts = explode('|', $url, 2);
            $parts = array_map('trim', $parts);
            return [
                'url' => $parts[0] ?? null,
                'desc' => $parts[1] ?? $parts[0] ?? null,
            ];
        };
        $result = array_map($filter, $urls);
        return $result;
    }

    /**
     * Get library branches
     *
     * @return array
     */
    public function getLibBranch()
    {
        return $this->fields['branch_display_mv'] ?? [];
    }

    /**
     * Get responsible people names
     *
     * @return array
     */
    public function getLibResponsibility()
    {
        return $this->fields['responsibility_display_mv'] ?? [];
    }

    /**
     * Get phone number
     *
     * @return array
     */
    public function getPhone()
    {
        return $this->fields['phone_display_mv'] ?? [];
    }

    /**
     * Get email address
     *
     * @return array
     */
    public function getEmail()
    {
        return $this->fields['email_display_mv'] ?? [];
    }

    /**
     * Get provided services
     *
     * @return array
     */
    public function getService()
    {
        return $this->fields['services_display_mv'] ?? [];
    }

    /**
     * Get library roles
     *
     * @return array
     */
    public function getFunction()
    {
        return $this->fields['function_display_mv'] ?? [];
    }

    /**
     * Get projects library participates in
     *
     * @return array
     */
    public function getProject()
    {
        return $this->fields['projects_display_mv'] ?? [];
    }

    /**
     * Get type of library
     *
     * @return array
     */
    public function getType()
    {
        return $this->fields['type_display_mv'] ?? [];
    }

    /**
     * Get ILL service information
     *
     * @return array
     */
    public function getMvs()
    {
        return $this->fields['mvs_display_mv'] ?? [];
    }

    /**
     * Get branch URL
     *
     * @return array
     */
    public function getBranchUrl()
    {
        return $this->fields['branchurl_display_mv'] ?? [];
    }

    /**
     * Get branches
     *
     * @return array
     */
    public function getBranches()
    {
        $branches = $this->fields['branch_display_mv'] ?? [];
        $result = [];
        foreach ($branches as $branch) {
            $fields = array_map('trim', explode('|', $branch));
            $branch = [
                'title'       => $fields[0],
                'address'     => $fields[1],
                'town'        => $fields[2] ?? null,
                'gps_display' => $fields[3] ?? null,
            ];
            $coordinates = $fields[4] ?? null;
            if ($coordinates != null) {
                [$lat, $lng] = explode(' ', $coordinates);
                $branch['coordinates'] = [
                    'lat' => floatval($lat),
                    'lng' => floatval($lng),
                ];
            }
            $result[] = $branch;
        }
        return $result;
    }

    /**
     * Get facet value for library represented by this record
     *
     * @return string|null
     */
    public function getBookSearchFilter()
    {
        $institution = $this->getCpkCode();
        $institutionsMappings = ($institution !== '')
            ? $this->facetsConfig->InstitutionsMappings->toArray() : [];
        $filterValue = $institutionsMappings[$institution] ?? null;
        if ($filterValue == null) {
            return null;
        }
        return urlencode(
            '~' . $this->institutionField .
            ':"' . $filterValue . '"'
        );
    }

    /**
     * Get GPS coordinates of library
     *
     * @return array
     */
    public function getGpsCoordinates()
    {
        $gps = $this->fields['gps_display'] ?? '';
        $coords = [];
        if ($gps != '') {
            [$coords['lat'], $coords['lng']] = array_map(
                'floatval',
                explode(' ', $gps, 2)
            );
        }
        return $coords;
    }

    /**
     * Does library has any additional data? (sigla or last update date)
     *
     * @return bool
     */
    public function hasAdditionalInfo()
    {
        return !empty($this->getSigla()) || !empty($this->getLastUpdated()) || !empty($this->getOrgCode());
    }

    /**
     * Does library has any contact defined?
     *
     * @return bool
     */
    public function hasContacts()
    {
        return !empty($this->getPhone())
            || !empty($this->getEmail())
            || !empty($this->getLibResponsibility());
    }

    /**
     * Does library has any provided service defined?
     *
     * @return bool
     */
    public function hasServices()
    {
        return !empty($this->getService())
            || !empty($this->getFunction())
            || !empty($this->getProject());
    }

    /**
     * Does library has a branch?
     *
     * @return bool
     */
    public function hasBranches()
    {
        return !empty($this->getLibBranch());
    }

    /**
     * Get handler for related
     *
     * @return array
     */
    public function getFilterParamsForRelated()
    {
        return ['handler' => 'morelikethislibrary'];
    }

    /**
     * Get Regional Library
     *
     * @return array
     */
    public function getRegLibrary()
    {
        $library       = $this->fields['reg_lib_id_display_mv'] ?? [];
        $parsedLibrary = empty($library) ? [] : explode('|', $library[0]);
        return empty($parsedLibrary) ? []
            : ['id' => $parsedLibrary[0], 'name' => $parsedLibrary[1]];
    }

    /**
     * Attach facets config to property
     *
     * @param \Laminas\Config\Config $facetsConfig Config for facets
     *
     * @return void
     */
    public function attachFacetsConfig($facetsConfig)
    {
        $this->facetsConfig = $facetsConfig;
    }

    /**
     * Return deduplicated records - array with key as institution source and
     * value with record ids or false if not supported
     *
     * @return array|false
     */
    public function getDeduplicatedRecords()
    {
        return false;
    }

    /**
     * Get translated region name
     *
     * @return string
     */
    public function getRegion(): string
    {
        $regionRaw = $this->fields['region_display'] ?? '';
        return ($regionRaw !== '')
            ? $this->translate('Region::' . $regionRaw)
            : '';
    }

    /**
     * Get source code for library involved in Knihovny.cz project
     *
     * @return string
     */
    public function getCpkCode(): string
    {
        return $this->fields['cpk_code_display'] ?? '';
    }

    /**
     * Return full name based on library source code
     *
     * @return string
     */
    public function getTranslatedNameBySource(): string
    {
        $sourceId = $this->getCpkCode();
        if ($sourceId !== '') {
            $translatedName = $this->translate('Source::' . $sourceId);
            return ($translatedName !== $sourceId) ? $translatedName : '';
        }
        return '';
    }

    /**
     * Get fake NBN for getting image from Obálkyknih.cz
     *
     * @return array
     */
    public function getCleanNBN()
    {
        $sigla = strtolower($this->getSigla());
        return ['nbn' => "$sigla-$sigla"];
    }

    /**
     * Get formats for display
     *
     * @return array
     */
    public function getFormats()
    {
        return ['libraries'];
    }

    /**
     * Creates query for wikidata links
     *
     * @return array[string query, array prefixes]
     */
    protected function getWikidataQuery(): array
    {
        $id = $this->getSigla();

        $queryPattern = <<<SPARQL
            SELECT ?wikidata ?wikidataLabel ?wheelchair ?wheelchairLabel %s
            WHERE
            {
                ?wikidata wdt:P9559 "%s" .
                OPTIONAL {
                    ?wikidata wdt:P2846 ?wheelchair .
                }
            %s

                SERVICE wikibase:label { bd:serviceParam wikibase:language "%s". }
            }
            SPARQL;

        $subquery = $this->createExternalIdentifiersSubquery(
            'wikidata',
            $this->wikiExternalLinks
        );

        $query = sprintf(
            $queryPattern,
            $subquery['variables'],
            addslashes($id),
            $subquery['where'],
            $this->getTranslatorLocale()
        );
        return [$query, ['wikibase', 'wdt', 'wd']];
    }

    /**
     * Get links to social networks
     *
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getSocialSitesLinks(): array
    {
        $data = $this->getWikidataData();
        return $this->formatLinks($data, $this->socialSites);
    }

    /**
     * Get information about ISDS (datova schranka)
     *
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getIsds(): array
    {
        $data = $this->getWikidataData();
        return $this->formatLinks($data, ['isds']);
    }

    /**
     * Get information about Code List for Cultural Heritage Organizations
     * (currated by Library of Congress)
     *
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getOrgCode(): array
    {
        $data = $this->getWikidataData();
        return $this->formatLinks($data, ['orgcode']);
    }

    /**
     * Get data for CSV export
     *
     * @return array
     */
    public function getCsvData(): array
    {
        $addresses = $this->getLibraryAddress();
        $phones = $this->getPhone();
        $emails = $this->getEmail();
        $websites = $this->getWebsites();
        $isds = $this->getIsds();
        return [
            'name' => $this->getTitle(),
            'sigla' => $this->getSigla(),
            'address' => array_shift($addresses),
            'phone' => array_shift($phones),
            'email' => array_shift($emails),
            'web' => array_shift($websites)['url'] ?? '',
            'isds' => array_shift($isds)['value'] ?? '',
            'type' => implode(',', $this->getType()),
            'note' => $this->getLibNote(),
            'note2' => $this->getLibNote2(),
        ];
    }

    /**
     * Get library sigla
     *
     * @return string Library sigla
     */
    public function getSiglaSearchTxt(): string
    {
        return $this->fields['sigla_search_txt'] ?? '';
    }

    /**
     * Get parent library sigla
     *
     * @return string Parent library sigla
     */
    public function getRegionalLibraryTxt(): string
    {
        return $this->fields['regional_library_txt'] ?? '';
    }

    /**
     * Get library functions
     *
     * @return array List of functions
     */
    public function getFunctionSearchTxtMv(): array
    {
        return $this->fields['function_search_txt_mv'] ?? [];
    }

    /**
     * Get library region name
     *
     * @return string Region name
     */
    public function getRegionSearchTxt(): string
    {
        return $this->fields['region_search_txt'] ?? '';
    }

    /**
     * Get library district name
     *
     * @return string District name
     */
    public function getDistrictSearchTxt(): string
    {
        return $this->fields['district_search_txt'] ?? '';
    }

    /**
     * Get library town name
     *
     * @return string Town name
     */
    public function getTownStr(): string
    {
        return $this->fields['town_str'] ?? '';
    }

    /**
     * Get wheelchair accessibility information about library building
     *
     * @return WheelchairAccessibility[]
     */
    public function getWheelchairAccessibility(): array
    {
        $data = $this->getWikidataData();
        $accessibilities = [];
        foreach ($data as $line) {
            if (!empty($line['wheelchair']['value'] ?? null)) {
                $accessibilities[$line['wheelchair']['value']]
                    = WheelchairAccessibility::from($line['wheelchair']['value']);
            }
        }
        return $accessibilities;
    }
}
