<?php
declare(strict_types=1);

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
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function getWikidataData(): array
    {
        $data = $this->getCachedData('wikidata');
        if (empty($data)) {
            $queryData = $this->tryMethod('getWikidataQuery');
            if (empty($queryData)) {
                return [];
            }
            [$query, $prefixes] = $queryData;
            $data = $this->sparqlService->query($query, $prefixes);
            $this->putCachedData('wikidata', $data);
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
}
