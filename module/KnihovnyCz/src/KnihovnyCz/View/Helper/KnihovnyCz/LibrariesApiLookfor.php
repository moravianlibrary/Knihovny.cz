<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use VuFindSearch\Query\Query;
use VuFindSearch\Query\QueryGroup;

/**
 * Class LibrariesApiLookfor
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class LibrariesApiLookfor
{
    /**
     * Create lookfor string, translate advanced search query into basic to be used
     * as query string for search API
     *
     * @param QueryGroup|Query $query Query or query group as used in original query
     *
     * @return string
     */
    public function __invoke(QueryGroup | Query $query): string
    {
        return $this->getLookfor($query);
    }

    /**
     * Create lookfor string, translate advanced search query into basic to be used
     * as query string for search API
     *
     * @param QueryGroup|Query $query Query or query group as used in original query
     *
     * @return string
     */
    protected function getLookfor(QueryGroup | Query $query): string
    {
        if ($query instanceof QueryGroup) {
            return $this->getLookforFromQueryGroup($query);
        } elseif ($query instanceof Query) {
            return $this->getLookforFromQuery($query);
        }
        return '';
    }

    /**
     * Return lookfor string for query group
     *
     * @param QueryGroup $queryGroup Query group
     *
     * @return string
     */
    protected function getLookforFromQueryGroup(QueryGroup $queryGroup): string
    {
        $operator = $queryGroup->getOperator();
        $queries = array_map(
            function ($query) {
                return $this->getLookfor($query);
            },
            $queryGroup->getQueries()
        );
        $queryPrefix = $queryGroup->isNegated() ? 'NOT' : '';
        return $queryPrefix . '(' . implode(" $operator ", $queries) . ')';
    }

    /**
     * Return lookfor string for one query
     *
     * @param Query $query Query
     *
     * @return string
     */
    protected function getLookforFromQuery(Query $query): string
    {
        $queryFieldsMapping = [
            'Name' => ['name_search_txt', 'name_alt_search_txt_mv'],
            'Town' => ['town_search_txt', 'address_search_txt_mv'],
            'Sigla' => ['sigla_search_txt'],
            'People' => ['responsibility_search_txt_mv'],
        ];
        $queryString = $string = $query->getString() ?? '';
        $fieldMapping = $queryFieldsMapping[$query->getHandler()] ?? [];
        if (!empty($fieldMapping)) {
            $subqueries = array_map(
                function ($fieldName) use ($string) {
                    return $fieldName . ':' . $string;
                },
                $fieldMapping
            );
            $queryString = '(' . implode(' OR ', $subqueries) . ')';
        }
        return $queryString;
    }
}
