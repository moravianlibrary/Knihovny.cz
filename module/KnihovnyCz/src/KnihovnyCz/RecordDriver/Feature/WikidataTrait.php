<?php

/**
 * Trait WikidataTrait
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2022.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDriver\Feature
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\RecordDriver\Feature;

/**
 * Trait WikidataTrait
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDriver\Feature
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait WikidataTrait
{
    /**
     * Wikidata sparql connector
     *
     * @var \KnihovnyCz\Wikidata\SparqlService
     */
    protected \KnihovnyCz\Wikidata\SparqlService $sparqlService;

    /**
     * Attach Wikidata SPARQL connector
     *
     * @param \KnihovnyCz\Wikidata\SparqlService $sparqlService SPARQL connector
     *
     * @return void
     */
    public function attachSparqlService(
        \KnihovnyCz\Wikidata\SparqlService $sparqlService
    ): void {
        $this->sparqlService = $sparqlService;
    }

    /**
     * Create Wikidata query for getting property values and corresponding formatters
     *
     * @param string $entityVariable           Wikidata entity variable
     * @param array  $identifiersAndProperties Array of identifiers => properties
     *
     * @return array
     */
    protected function createExternalIdentifiersSubquery(
        string $entityVariable,
        array $identifiersAndProperties
    ): array {
        $urlQueryPattern = <<<SPARQL
    OPTIONAL {
        wd:%s wdt:P1630 ?%sFormatter .
        ?%s wdt:%s ?%s .
    }

SPARQL;
        $subquery = '';
        foreach ($identifiersAndProperties as $externalIdentifier => $property) {
            $subquery .= sprintf(
                $urlQueryPattern,
                $property,
                $externalIdentifier,
                $entityVariable,
                $property,
                $externalIdentifier
            );
        }
        $fields = array_map(
            function ($field) {
                return '?' . $field;
            },
            array_keys($identifiersAndProperties)
        );
        $formatters = array_map(
            function ($field) {
                return $field . 'Formatter';
            },
            $fields
        );
        $variables = array_merge($fields, $formatters);
        return [
            'variables' => implode(' ', $variables),
            'where' => $subquery,
        ];
    }

    /**
     * Get data for this authority record from wikidata
     *
     * @param string $queryMethod Method to get query
     *
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function getWikidataData(string $queryMethod = 'getWikidataQuery'): array
    {
        $cacheKey = $queryMethod . '_' . $this->getTranslatorLocale();
        $data = $this->getCachedData($cacheKey);
        if (empty($data)) {
            $queryData = $this->tryMethod($queryMethod);
            if (empty($queryData)) {
                return [];
            }
            [$query, $prefixes] = $queryData;
            $data = $this->sparqlService->query($query, $prefixes);
            $this->putCachedData($cacheKey, $data);
        }
        return $data;
    }

    /**
     * Format links using wikidata formatters
     *
     * @param array $data          Data from Wikidata
     * @param array $linksToFormat Links to format
     *
     * @return array
     */
    protected function formatLinks(array $data, array $linksToFormat): array
    {
        $links = [];
        foreach ($data as $link) {
            foreach ($linksToFormat as $field) {
                $formatter = $field . 'Formatter';
                if (isset($link[$field]['value'])) {
                    $url = $link[$field]['value'];
                    if (isset($link[$formatter]['value'])) {
                        $url = str_replace(
                            '$1',
                            urlencode($link[$field]['value']),
                            $link[$formatter]['value']
                        );
                    }
                    $links[] = [
                        'url' => $url,
                        'label' => $field,
                        'value' => $link[$field]['value'],
                    ];
                }
            }
        }
        return $links;
    }

    /**
     * Get information about cited documents
     *
     * @return array
     */
    public function getCitedDocuments(): array
    {
        $data = $this->getCachedData('cites');
        if (empty($data)) {
            $data = $this->getCitesFromWikidata();
            $this->putCachedData('wikidata', $data);
        }
        return $data;
    }

    /**
     * Get information about cited documents from wikidata
     *
     * @return array
     */
    protected function getCitesFromWikidata(): array
    {
        $doi = $this->getCleanDOI();
        if (empty($doi)) {
            return [];
        }
        $doi = strtoupper($doi);
        $queryPattern = <<<SPARQL
SELECT ?item ?cite ?citeLabel ?authorLabel ?authorStringLabel ?doi
WHERE
{
  ?item wdt:P356 "%s" ;
        wdt:P2860 ?cite .
  ?cite wdt:P356 ?doi .
  OPTIONAL {
    ?cite wdt:P50 ?author .
  }
  OPTIONAL {
    ?cite wdt:P2093 ?authorString .
  }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "%s". }
}
SPARQL;
        $lang = ($this->getTranslatorLocale() === 'cs') ? 'cs,en' : 'en,cs';
        $query = sprintf($queryPattern, $doi, $lang);
        $data = $this->sparqlService->query($query, ['wdt', 'wd', 'wikibase']);
        $citedDocuments = [];
        foreach ($data as $item) {
            if (isset($item['authorLabel']['value'])) {
                $citedDocuments[$item['cite']['value']]['authors'][]
                    = $item['authorLabel']['value'];
            }
            if (isset($item['authorStringLabel']['value'])) {
                $citedDocuments[$item['cite']['value']]['authors'][]
                    = $item['authorStringLabel']['value'];
            }
            $citedDocuments[$item['cite']['value']]['doi'] = $item['doi']['value'];
            $citedDocuments[$item['cite']['value']]['title']
                = $item['citeLabel']['value'];
        }
        return $citedDocuments;
    }
}
