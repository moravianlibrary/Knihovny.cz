<?php
/**
 * Knihovny.cz solr dictionary record driver
 *
 * PHP version 7
 *
 * Copyright (C) The Moravian Library 2015-2019.
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
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
namespace KnihovnyCz\RecordDriver;

/**
 * Knihovny.cz solr dictionary record driver
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class SolrDictionary extends \KnihovnyCz\RecordDriver\SolrMarc
{
    /**
     * Record data formatter key
     *
     * @return string
     */
    protected string $recordDataFormatterKey = 'dictionary';

    /**
     * Get explanation.
     *
     * @return array $field
     */
    public function getSummary()
    {
        return isset($this->fields['explanation_display'])
            ? [$this->fields['explanation_display']] : [];
    }

    /**
     * Get term author list
     *
     * @return array Term author list or empty array
     */
    public function getTermAuthors()
    {
        return $this->fields['author_term_display_mv'] ?? [];
    }

    /**
     * Get name, shown as title of record.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->fields['title'] ?? '';
    }

    /**
     * Get english term.
     *
     * @return string
     */
    public function getEnglish()
    {
        return $this->fields['english_display'] ?? '';
    }

    /**
     * Get relative terms.
     *
     * @return array
     */
    public function getRelatives()
    {
        return $this->fields['relative_display_mv'] ?? [];
    }

    /**
     * Get alternative terms.
     *
     * @return array
     */
    public function getAlternatives()
    {
        return $this->fields['alternative_display_mv'] ?? [];
    }

    /**
     * Get source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->fields['source_display'] ?? '';
    }

    /**
     * Get handler for related
     *
     * @return array
     */
    public function getFilterParamsForRelated()
    {
        return ['handler' => 'morelikethisdictionary'];
    }

    /**
     * Get data from Wikidata
     *
     * @return array
     */
    public function getWikidataInfo(): array
    {
        [, $id] = explode('.', $this->getUniqueID());
        $queryPattern = <<<SPARQL
SELECT ?tdkiv ?tdkivLabel ?article
WHERE
{
	?tdkiv wdt:P5398 "%s" .
	OPTIONAL {
		?article schema:about ?tdkiv .
		?article schema:inLanguage "%s".
		?article schema:isPartOf/wikibase:wikiGroup "wikipedia" .
	}

	SERVICE wikibase:label { bd:serviceParam wikibase:language "%s". }
}
SPARQL;
        $query = sprintf(
            $queryPattern,
            addslashes($id),
            $this->getTranslatorLocale(),
            $this->getTranslatorLocale()
        );
        return $this->sparqlService->query($query, ['schema', 'wikibase', 'wdt']);
    }

    /**
     * Get links to external websites from wikidata
     *
     * @return array
     */
    public function getWikidataLinks(): array
    {
        $info = $this->getWikidataInfo();
        $info = array_filter(
            $info,
            function ($item) {
                return isset($item['article']['value']);
            }
        );
        return array_map(
            function ($item) {
                return $item['article']['value'];
            },
            $info
        );
    }
}
