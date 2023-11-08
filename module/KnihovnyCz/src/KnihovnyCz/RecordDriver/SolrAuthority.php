<?php

namespace KnihovnyCz\RecordDriver;

use VuFindSearch\Command\SearchCommand;

/**
 * Knihovny.cz solr authority record driver
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class SolrAuthority extends \KnihovnyCz\RecordDriver\SolrMarc
{
    protected $wikiExternalLinks = [
        'orcid' => 'P496',
        'isni' => 'P213',
        'abart' => 'P6844',
        'viaf' => 'P214',
        'cbdb' => 'P10400',
        'dbknih' => 'P10387',
        'csfd' => 'P2605',
        'twitter' => 'P2002',
        'instagram' => 'P2003',
        'wikitree' => 'P2949',
        'fide' => 'P1440',
    ];

    protected array $wikiSiteLinks = ['wikipedia', 'wikiquote', 'wikisource',];

    protected array $identifiers = ['wikidata', 'viaf', 'isni', 'orcid',];

    protected array $externalLinks = [
        'wikipedia',
        'wikiquote',
        'wikisource',
        'abart',
        'cbdb',
        'dbknih',
        'csfd',
        'twitter',
        'instagram',
        'wikitree',
        'fide',
    ];

    /**
     * Record data formatter key
     *
     * @return string
     */
    protected string $recordDataFormatterKey = 'authority';

    /**
     * Record data description
     *
     * @return string
     */
    protected string $recordDataTypeDescription = 'Authority Details';

    /**
     * Get the full title of the record.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->fields['personal_name_display'] ?? '';
    }

    /**
     * Get the alternatives of the full name.
     *
     * @return array of alternative names
     */
    public function getAddedEntryPersonalNames()
    {
        return $this->fields['alternative_name_display_mv'] ?? [];
    }

    /**
     * Get the authority's pseudonyms.
     *
     * @return array
     */
    public function getPseudonyms()
    {
        $pseudonyms = [];
        $names = $this->fields['pseudonym_name_display_mv'] ?? [];
        $ids = $this->fields['pseudonym_record_ids_display_mv'] ?? [];
        if ($names && $ids) {
            $pseudonyms = array_combine($names, $ids);
        }
        return $pseudonyms ? $pseudonyms : [];
    }

    /**
     * Get authority's source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->fields['source_display_mv'][0] ?? '';
    }

    /**
     * Get the authority's name, shown as title of record.
     *
     * @return string
     */
    public function getHighlightedTitle()
    {
        return rtrim($this->getTitle(), ',');
    }

    /**
     * Get the authority's bibliographic details.
     *
     * @return array $field
     */
    public function getSummary()
    {
        return $this->fields['bibliographic_details_display_mv'] ?? [];
    }

    /**
     * Get the bibliographic details of authority.
     *
     * @return string $details
     */
    public function getBibliographicDetails()
    {
        return isset($this->fields['bibliographic_details_display_mv'])
            ? $this->fields['bibliographic_details_display_mv'][0] : '';
    }

    /**
     * Get id_authority.
     *
     * @return string
     */
    public function getAuthorityId()
    {
        return $this->fields['authority_id_display'] ?? '';
    }

    /**
     * Get count of results for given search
     *
     * @param string $field Field to search
     * @param string $value Value to search for
     *
     * @return int
     */
    protected function getCountByField(string $field, string $value)
    {
        $safeValue = addcslashes($value, '"');
        $query = new \VuFindSearch\Query\Query($field . ':"' . $safeValue . '"');
        $params = new \VuFindSearch\ParamBag(['hl' => ['false']]);
        $command = new SearchCommand($this->sourceIdentifier, $query, 0, 0, $params);
        return $this->searchService->invoke($command)->getResult()->getTotal();
    }

    /**
     * Returns true, if authority has publications.
     *
     * @return bool
     */
    public function hasPublications()
    {
        return 1 < $this->getCountByField(
            'authorCorporation_search_txt_mv',
            $this->getAuthorityId()
        );
    }

    /**
     * Returns true, if there are publications about this authority.
     *
     * @return bool
     */
    public function hasPublicationsAbout()
    {
        return 0 < $this->getCountByField(
            'subjectKeywords_search_txt_mv',
            $this->getAuthorityId()
        );
    }

    /**
     * Get link to search publications of authority.
     *
     * @return string|null
     */
    public function getPublicationsUrl()
    {
        $url = null;
        if ($this->hasPublications()) {
            $url = '/Search/Results?'
                . 'sort=relevance&join=AND&type0[]=adv_search_author_corporation'
                . '&bool0[]=AND&searchTypeTemplate=advanced&lookfor0[]='
                . $this->getAuthorityId();
        }
        return $url;
    }

    /**
     * Get link to search publications about authority.
     *
     * @return string|null
     */
    public function getPublicationsAboutUrl()
    {
        $url = null;
        if ($this->hasPublicationsAbout()) {
            $url = '/Search/Results?'
                . 'sort=relevance&join=AND&type0[]=adv_search_subject_keywords'
                . '&bool0[]=AND&searchTypeTemplate=advanced&lookfor0[]='
                . $this->getAuthorityId();
        }
        return $url;
    }

    /**
     * Get urls related to this record, publications of this authority
     *  and publications about this authority
     *
     * @return array
     */
    public function getRelatedUrls()
    {
        $urls = [];
        $publicationsUrl = $this->getPublicationsUrl();
        $publicationsAboutUrl = $this->getPublicationsAboutUrl();
        if ($publicationsUrl) {
            $urls[] = [
                'url' => $publicationsUrl,
                'desc' => 'Show publications of this person',
            ];
        }
        if ($publicationsAboutUrl) {
            $urls[] = [
                'url' => $publicationsAboutUrl,
                'desc' =>  'Show publications about this person',
            ];
        }
        return $urls;
    }

    /**
     * Returns array with one key: authority_id
     *
     * @param string $size Size of thumbnail (small, medium or large -- small is
     * default).
     *
     * @return string|array|bool
     */
    public function getThumbnail($size = 'small')
    {
        return [
            'recordid' => $this->getUniqueID(),
            'source' => $this->getSourceIdentifier(),
            'size' => $size,
            'title' => $this->getTitle(),
            'nbn' => $this->getAuthorityId(),
        ];
    }

    /**
     * Get authority occupation
     *
     * @return array
     */
    public function getOccupation(): array
    {
        return $this->fields['occupation_display_mv'] ?? [];
    }

    /**
     * Return NKCR AUT ID or OsobnostiRegionu.cz Id
     *
     * @return string
     */
    protected function getCombinedAuthorityId(): string
    {
        $id = $this->getAuthorityId();
        if (!empty($id)) {
            return $id;
        }
        [, $id2] = explode('.', $this->getUniqueID());
        return $id2;
    }

    /**
     * Get links to external websites from wikidata
     *
     * @return array
     */
    public function getWikidataLinks(): array
    {
        $data = $this->getWikidataData();
        $linkFields = array_merge(
            ['wikidata'],
            $this->wikiSiteLinks,
            array_keys($this->wikiExternalLinks)
        );
        return $this->formatLinks($data, $linkFields);
    }

    /**
     * Creates query for wikidata links
     *
     * @return array[string query, array prefixes]
     */
    protected function getWikidataQuery(): array
    {
        $id = $this->getCombinedAuthorityId();

        $siteLinksQueryPattern = <<<SPARQL
            	OPTIONAL {
            		?%s schema:about ?wikidata .
            		?%s schema:inLanguage "%s".
            		?%s schema:isPartOf/wikibase:wikiGroup "%s" .
            	}

            SPARQL;

        $queryPattern = <<<SPARQL
            SELECT ?wikidata ?wikidataLabel %s %s ?signature ?pronunciation ?natLangPronunciation ?ipa ?natLangIpa
            WHERE
            {
            	?wikidata wdt:P691|wdt:P9299 "%s" .
            %s
            %s
                OPTIONAL {
                    ?wikidata wdt:P109 ?signature .
                }

                OPTIONAL {
                    ?wikidata wdt:P443 ?pronunciation .
                }

                OPTIONAL {
                    ?wikidata p:P1559 [pq:P443 ?natLangPronunciation].
                }

                OPTIONAL {
                    ?wikidata wdt:P898 ?ipa .
                }

                OPTIONAL {
                    ?wikidata p:P1559 [pq:P898 ?natLangIpa].
                }

            	SERVICE wikibase:label { bd:serviceParam wikibase:language "%s". }
            }
            LIMIT 1
            SPARQL;
        $siteLinksFields = array_map(
            function ($siteField) {
                return '?' . $siteField;
            },
            $this->wikiSiteLinks
        );
        $subquery = $this->createExternalIdentifiersSubquery(
            'wikidata',
            $this->wikiExternalLinks
        );

        $querySiteLinks = '';
        foreach ($this->wikiSiteLinks as $site) {
            $querySiteLinks .= sprintf(
                $siteLinksQueryPattern,
                $site,
                $site,
                $this->getTranslatorLocale(),
                $site,
                $site
            );
        }
        $query = sprintf(
            $queryPattern,
            implode(' ', $siteLinksFields),
            $subquery['variables'],
            addslashes($id),
            $querySiteLinks,
            $subquery['where'],
            $this->getTranslatorLocale()
        );
        return [$query, ['schema', 'wikibase', 'wdt', 'wd']];
    }

    /**
     * Get image URL of this person's signature
     *
     * @return string
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function getSignature(): string
    {
        $data = $this->getWikidataData();
        foreach ($data as $link) {
            if (isset($link['signature']['value'])) {
                return str_replace(
                    'http://',
                    'https://',
                    $link['signature']['value']
                );
            }
        }

        return '';
    }

    /**
     * Get sound file URL and IPA transcription of this person's name
     *
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function getPronunciation(): array
    {
        $data = $this->getWikidataData();
        $return = [];
        foreach ($data as $link) {
            $pronunciation = $link['natLangPronunciation']['value'] ?? $link['pronunciation']['value'] ?? null;
            if ($pronunciation) {
                $return['pronunciation'] = str_replace(
                    'http://',
                    'https://',
                    $pronunciation
                );
            }
            $ipa = $link['natLangIpa']['value'] ?? $link['ipa']['value'] ?? null;
            if ($ipa) {
                $return['ipa'] = $ipa;
            }
        }

        return $return;
    }

    /**
     * Get links to external sites/databases by type
     *
     * @param string $type Type of link
     *
     * @return array
     */
    protected function getExternalLinksByType(string $type): array
    {
        return array_filter(
            $this->getWikidataLinks(),
            function ($link) use ($type) {
                return in_array($link['label'], $this->$type);
            }
        );
    }

    /**
     * Get links to external sites
     *
     * @return array
     */
    public function getExternalLinks(): array
    {
        return $this->getExternalLinksByType('externalLinks');
    }

    /**
     * Get links to external databases
     *
     * @return array
     */
    public function getIdentifiersLinks(): array
    {
        return $this->getExternalLinksByType('identifiers');
    }

    /**
     * Get query for related people
     *
     * @return array
     */
    protected function getRelatedQuery(): array
    {
        $id = $this->getCombinedAuthorityId();
        $lang = $this->getTranslatorLocale();
        $queryPattern = <<<SPARQL
            SELECT DISTINCT ?relatedId ?propLabel WHERE {
              {
                SELECT ?prop ?relatedId {
                  ?item wdt:P691|wdt:P9299 "%s" .
                  ?related wdt:P691|wdt:P9299 ?relatedId .
                  ?item ?prop ?related .
                  ?item wdt:P31 wd:Q5 .
                  ?related wdt:P31 wd:Q5 .
                } LIMIT 100
              }
              ?property wikibase:directClaim ?prop .
              optional { ?property rdfs:label ?propLabel filter(lang(?propLabel)="%s"). }
            }
            SPARQL;
        return [
            sprintf($queryPattern, addslashes($id), $lang),
            ['rdfs', 'wikibase', 'wdt', 'wd'],
        ];
    }

    /**
     * Helper method to get authority record by either NKCR AUT ID and Osobnostiregionu id
     *
     * @param string $id NKCR AUT ID or Osobnostiregionu id
     *
     * @return SolrDefault|null
     * @throws \Exception
     */
    protected function getRecordById(string $id): ?SolrDefault
    {
        if (is_numeric($id)) {
            return $this->recordLoader->load('osobnosti.' . $id);
        }
        $searchQuery = new \VuFindSearch\Query\Query('id_authority:' . $id);
        $command = new SearchCommand($this->getSearchBackendIdentifier(), $searchQuery, 0, 1);
        $searchResults = $this->searchService->invoke($command)->getResult();
        foreach ($searchResults->getRecords() as $record) {
            return $record;
        }
        return null;
    }

    /**
     * Get related people
     *
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function getRelatedAuthorities(): array
    {
        $data = $this->getWikidataData('getRelatedQuery');
        $return = [];
        foreach ($data as $authority) {
            $id = $authority['relatedId']['value'] ?? '';
            if (!empty($id)) {
                try {
                    $record = $this->getRecordById($id);
                } catch (\Exception $e) {
                    continue;
                }
                $return[] = [
                    'record' => $record,
                    'label' => $authority['propLabel']['value'] ?? '',
                ];
            }
        }
        return $return;
    }

    /**
     * Return Wikidata item id (in form uf URI)
     *
     * @return string
     */
    public function getWikidataId(): string
    {
        $links = $this->getExternalLinksByType('identifiers');
        return $links['wikidata']['url'] ?? '';
    }
}
