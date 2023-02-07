<?php
declare(strict_types=1);

/**
 * Class SuggestionFilter
 *
 * PHP version 7
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
 * @package  Autocomplete
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Autocomplete;

/**
 * Class SuggestionFilter
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\View_Helpers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SuggestionFilter
{
    /**
     * Helper for escaping HTML
     *
     * @var EscapeHtml
     */
    protected $escapeHtml;

    /**
     * Constructor
     *
     * @param EscapeHtml $escapeHtml Escape HTML helper
     */
    public function __construct(\Laminas\View\Helper\EscapeHtml $escapeHtml)
    {
        $this->escapeHtml = $escapeHtml;
    }

    /**
     * Filter suggestions and apply highlightin
     *
     * @param string $query   The user query
     * @param array  $results Suggestions to filter
     *
     * @return array Filtered suggestions according to query
     */
    public function filter($query, $results)
    {
        $highlighter = new Highlighter($query);
        $filtered = [];
        $normalizedQuery = $this->normalize($query);
        $queryParts = (array)preg_split('/\s+/', trim($normalizedQuery));
        $queryParts = array_map(
            function ($part) {
                return ' ' . $part;
            },
            $queryParts
        );
        $queryPartsCount = count($queryParts);

        foreach ($results as $result) {
            $search = ' ' . $this->normalize((string)$result);
            $matchedQueryParts = 0;
            foreach ($queryParts as $queryPart) {
                if (stripos($search, $queryPart) !== false) {
                    $matchedQueryParts++;
                }
            }
            if ($queryPartsCount == $matchedQueryParts) {
                $filtered[] = [
                    'label' => $highlighter->highlight(($this->escapeHtml)($result)),
                    'value' => $result,
                ];
            }
        }

        return $filtered;
    }

    /**
     * Normalize query
     *
     * @param string $query query to normalize
     *
     * @return string normalized query with removed diacritic
     */
    protected function normalize($query)
    {
        return (string)iconv("UTF-8", "ASCII//TRANSLIT", $query);
    }
}
