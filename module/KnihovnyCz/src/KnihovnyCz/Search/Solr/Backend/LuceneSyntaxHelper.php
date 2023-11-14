<?php

declare(strict_types=1);

namespace KnihovnyCz\Search\Solr\Backend;

use VuFindSearch\Backend\Solr\LuceneSyntaxHelper as Base;

/**
 * Class LuceneSyntaxHelper
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class LuceneSyntaxHelper extends Base
{
    protected ?OrQueryRewriter $orQueryRewriter = null;

    /**
     * Constructor.
     *
     * @param bool|string     $csBools  Case sensitive Booleans setting
     * @param bool            $csRanges Case sensitive ranges setting
     * @param OrQueryRewriter $rewriter Fixer for queries with OR operator
     */
    public function __construct($csBools = true, $csRanges = true, $rewriter = null)
    {
        $this->caseSensitiveBooleans = $csBools;
        $this->caseSensitiveRanges = $csRanges;
        $this->orQueryRewriter = $rewriter;
    }

    /**
     * Return normalized input string.
     *
     * @param string $searchString Input search string
     *
     * @return string
     */
    public function normalizeSearchString($searchString): string
    {
        $searchString = parent::normalizeSearchString($searchString);
        if ($this->orQueryRewriter !== null && str_contains($searchString, ' OR ')) {
            $searchString = $this->orQueryRewriter->tryRewrite($searchString);
        }
        return $searchString;
    }
}
