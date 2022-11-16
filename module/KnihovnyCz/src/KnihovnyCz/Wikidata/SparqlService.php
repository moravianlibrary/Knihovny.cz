<?php
declare(strict_types=1);

/**
 * Class SparqlService
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
 * @package  KnihovnyCz\Wikidata
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Wikidata;

use GuzzleHttp\Psr7\Request;
use KnihovnyCz\Service\GuzzleHttpService;

/**
 * Class SparqlService
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Wikidata
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SparqlService
{
    protected array $prefixes = [
        'bd' => 'http://www.bigdata.com/rdf#',
        'cc' => 'http://creativecommons.org/ns#',
        'dct' => 'http://purl.org/dc/terms/',
        'geo' => 'http://www.opengis.net/ont/geosparql#',
        'hint' => 'http://www.bigdata.com/queryHints#',
        'ontolex' => 'http://www.w3.org/ns/lemon/ontolex#',
        'owl' => 'http://www.w3.org/2002/07/owl#',
        'prov' => 'http://www.w3.org/ns/prov#',
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
        'schema' => 'http://schema.org/',
        'skos' => 'http://www.w3.org/2004/02/skos/core#',
        'xsd' => 'http://www.w3.org/2001/XMLSchema#',
        'p' => 'http://www.wikidata.org/prop/',
        'pq' => 'http://www.wikidata.org/prop/qualifier/',
        'pqn' => 'http://www.wikidata.org/prop/qualifier/value-normalized/',
        'pqv' => 'http://www.wikidata.org/prop/qualifier/value/',
        'pr' => 'http://www.wikidata.org/prop/reference/',
        'prn' => 'http://www.wikidata.org/prop/reference/value-normalized/',
        'prv' => 'http://www.wikidata.org/prop/reference/value/',
        'psv' => 'http://www.wikidata.org/prop/statement/value/',
        'ps' => 'http://www.wikidata.org/prop/statement/',
        'psn' => 'http://www.wikidata.org/prop/statement/value-normalized/',
        'wd' => 'http://www.wikidata.org/entity/',
        'wdata' => 'http://www.wikidata.org/wiki/Special:EntityData/',
        'wdno' => 'http://www.wikidata.org/prop/novalue/',
        'wdref' => 'http://www.wikidata.org/reference/',
        'wds' => 'http://www.wikidata.org/entity/statement/',
        'wdt' => 'http://www.wikidata.org/prop/direct/',
        'wdtn' => 'http://www.wikidata.org/prop/direct-normalized/',
        'wdv' => 'http://www.wikidata.org/value/',
        'wikibase' => 'http://wikiba.se/ontology#',
    ];

    protected string $sparqlUrl = 'https://query.wikidata.org/sparql';

    protected string $version;

    protected string $vufindVersion;

    /**
     * Http service
     *
     * @var GuzzleHttpService
     */
    protected GuzzleHttpService $httpService;

    /**
     * Constructor
     *
     * @param GuzzleHttpService $httpService   HTTP service
     * @param string            $version       Knihovny.cz version
     * @param string            $vufindVersion VuFind version
     */
    public function __construct(
        GuzzleHttpService $httpService,
        string $version,
        string $vufindVersion
    ) {
        $this->httpService = $httpService;
        $this->version = $version;
        $this->vufindVersion = $vufindVersion;
    }

    /**
     * Get HTTP client
     *
     * @return \Http\Client\HttpClient
     */
    protected function getHttpClient(): \Http\Client\HttpClient
    {
        return $this->httpService->createClient(['base_uri' => $this->sparqlUrl]);
    }

    /**
     * Query Wikidata SPARQL endpoint
     *
     * @param string $query    SPARQL query
     * @param array  $prefixes Prefixes to be added to query
     *
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function query(
        string $query,
        array $prefixes = ['schema', 'wikibase', 'wdt']
    ): array {
        $format = 'json';
        $query = $this->formatPrefixes($prefixes) . "\n" . $query;
        $url = '?' . http_build_query(compact(['query', 'format']));
        $request = new Request('GET', $url, $this->getHeaders());
        $response = $this->getHttpClient()->sendRequest($request);
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        return $data['results']['bindings'] ?? [];
    }

    /**
     * Format prefixes needed for query
     *
     * @param array $prefixes Prefixes
     *
     * @return string
     */
    protected function formatPrefixes(array $prefixes): string
    {
        $formatted = [];
        foreach ($prefixes as $prefix) {
            if (isset($this->prefixes[$prefix])) {
                $formatted[] = sprintf(
                    'PREFIX %s: <%s>',
                    $prefix,
                    $this->prefixes[$prefix]
                );
            }
        }
        return implode("\n", $formatted);
    }

    /**
     * Get headers
     *
     * @return array
     */
    protected function getHeaders(): array
    {
        $userAgent = sprintf(
            'Knihovny.cz/%s (%s; cpk-support@mzk.cz) VuFind/%s',
            $this->version,
            'https://www.knihovny.cz/Content/o-portalu',
            $this->vufindVersion
        );
        return [
            'Accept: application/sparql-results+json',
            'User-Agent' => $userAgent,
        ];
    }
}
