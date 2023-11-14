<?php

declare(strict_types=1);

namespace KnihovnyCzTest\Search\Solr\Backend;

use KnihovnyCz\Search\Solr\Backend\OrQueryRewriter;

/**
 * Class OrQueryRewriterTest
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class OrQueryRewriterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test rewriting queries
     *
     * @return void
     */
    public function testRewriter(): void
    {
        $queries = [
            [
                'query'  => 'Komiksová chemie OR Dávám tomu rok',
                'result' => '(Komiksová chemie) OR (Dávám tomu rok)',
            ],
            [
                'query'  => 'author:novak OR author:zatopek',
                'result' => '(author:novak) OR (author:zatopek)',
            ],
            [
                'query'  => 'author:"zatopek emil" OR běh',
                'result' => '(author:"zatopek emil") OR (běh)',
            ],
            [
                'query'  => 'zachránář* OR "jakost - životní styl"',
                'result' => '(zachránář*) OR ("jakost - životní styl")',
            ],
            [
                'query'  => 'zachránář* OR "jakost - životní styl"',
                'result' => '(zachránář*) OR ("jakost - životní styl")',
            ],
            [
                'query'  => 'zachránář? OR "rodinná výchova : zdravý styl?"',
                'result' => '(zachránář?) OR ("rodinná výchova : zdravý styl?")',
            ],
            [
                'query'  => '(Komiksová chemie) OR (Dávám tomu rok)',
                'result' => '((Komiksová chemie)) OR ((Dávám tomu rok))',
            ],
            [
                'query'  => '(Komiksová chemie) OR Dávám tomu rok',
                'result' => '((Komiksová chemie)) OR (Dávám tomu rok)',
            ],
            [
                'query'  => '"Komiksová chemie" OR Dávám tomu rok',
                'result' => '("Komiksová chemie") OR (Dávám tomu rok)',
            ],
            [
                'query'  => '"Komiksová chemie"OR(Dávám tomu rok)',
                'result' => '("Komiksová chemie") OR ((Dávám tomu rok))',
            ],
            [
                'query'  => 'gastroezofageální reflux OR GERD',
                'result' => '(gastroezofageální reflux) OR (GERD)',
            ],
            [
                'query'  => '((gastroezofageální OR choroba jícnu) reflux) OR GERD',
                'result' => '((((gastroezofageální) OR (choroba jícnu)) reflux)) OR (GERD)',
            ],
            [
                'query'  => '((((gastroezofageální) OR (choroba jícnu)) reflux)) OR (GERD)',
                'result' => '((((((gastroezofageální)) OR ((choroba jícnu))) reflux))) OR ((GERD))',
            ],
            [
                'query'  => 'm*cha OR m?cha',
                'result' => '(m*cha) OR (m?cha)',
            ],
            [
                'query'  => '"m*cha" OR "m?cha"',
                'result' => '("m*cha") OR ("m?cha")',
            ],
            [
                'query'  => '"(jihlav? OR brn*)" OR praha',
                'result' => '("(jihlav? OR brn*)") OR (praha)',
            ],
            [
                'query'  => 'jihlava AND brno OR praha AND kolin',
                'result' => '(jihlava AND brno) OR (praha AND kolin)',
            ],
            [
                'query'  => '"Historie. (1918-1938)" OR "Historie. (1938-1945)"',
                'result' => '("Historie. (1918-1938)") OR ("Historie. (1938-1945)")',
            ],
            [
                'query'  => 'Visarionovič~0.8 OR stalin',
                'result' => '(Visarionovič~0.8) OR (stalin)',
            ],
            [
                'query'  => '"druha valka"~2 OR "prvni valka"~2',
                'result' => '("druha valka"~2) OR ("prvni valka"~2)',
            ],
            [
                'query'  => 'stalin NOT lenin OR chruščov',
                'result' => '(stalin NOT lenin) OR (chruščov)',
            ],
            [
                'query'  => '+stalin -lenin OR chruščov',
                'result' => '(+stalin -lenin) OR (chruščov)',
            ],
            [
                'query'  => 'title:{Ma TO Mc} OR published:[2001 TO 2003]',
                'result' => '(title:{Ma TO Mc}) OR (published:[2001 TO 2003])',
            ],
            [
                'query'  => 'Mácha OR Máj^8 básně^6',
                'result' => '(Mácha) OR (Máj^8 básně^6)',
            ],
            [
                'query'  => 'Mácha OR "Máj básně"^6',
                'result' => '(Mácha) OR ("Máj básně"^6)',
            ],
            [
                'query'  => 'author_autocomplete:Mácha~1 OR title:"Máj básně"^6',
                'result' => '(author_autocomplete:Mácha~1) OR (title:"Máj básně"^6)',
            ],
            [
                'query'  => 'NOT lenin* OR stalin*',
                'result' => '(NOT lenin*) OR (stalin*)',
            ],
            [
                'query'  => "Poe's Raven OR Joe's pub",
                'result' => "(Poe's Raven) OR (Joe's pub)",
            ],
            [
                'query'  => "\"Poe's Raven\" OR \"Joe's pub\"",
                'result' => "(\"Poe's Raven\") OR (\"Joe's pub\")",
            ],
            [
                'query'  => 'romeo',
                'result' => 'romeo',
            ],
            [
                'query'  => 'romeo and julie or shakespeare',
                'result' => 'romeo and julie or shakespeare',
            ],
            [
                'query'  => 'macha AND (maj OR (zivot AND dilo))',
                'result' => 'macha AND ((maj) OR ((zivot AND dilo)))',
            ],
            [
                'query'  => 'fialka OR macha AND (maj OR (zivot AND dilo))',
                'result' => '(fialka) OR (macha AND ((maj) OR ((zivot AND dilo))))',
            ],
            [
                'query'  => 'macha AND ((maj) OR ((zivot AND dilo)))',
                'result' => 'macha AND (((maj)) OR (((zivot AND dilo))))',
            ],
            [
                'query' => '("Komiksová chemie") OR ((Dávám tomu rok))',
                'result' => '(("Komiksová chemie")) OR (((Dávám tomu rok)))',
            ],
            [
                'query' => 'author_autocomplete:"Komiksová chemie" OR author_autocomplete:"Dávám tomu rok"',
                'result' => '(author_autocomplete:"Komiksová chemie") OR (author_autocomplete:"Dávám tomu rok")',
            ],
            [
                'query' => 'author_autocomplete:(novak OR novotny)',
                'result' => 'author_autocomplete:((novak) OR (novotny))',
            ],
            [
                'query' => 'author_autocomplete:("jan novak" OR "jiri novotny")',
                'result' => 'author_autocomplete:(("jan novak") OR ("jiri novotny"))',
            ],
        ];
        $rewriter = $this->getOrQueryRewriter();
        foreach ($queries as $query) {
            $orig = $query['query'];
            $expected = $query['result'];
            $actual = $rewriter->rewrite($orig);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * Test bad queries
     *
     * @return void
     */
    public function testBadQueries(): void
    {
        $queries = [
            'GERD OR (chemie',
            'GERD OR (chemie))',
            'GERD OR ((chemie)',
            '(GERD OR chemie',
            'GERD OR chemie)',
            ')',
            '(',
        ];
        $rewriter = $this->getOrQueryRewriter();
        foreach ($queries as $query) {
            try {
                $rewriter->rewrite($query);
                $this->fail("Query '$query' did not fail");
            } catch (\Parle\LexerException $le) {
                // expected
            }
        }
    }

    /**
     * Method to get rewriter for OR queries.
     *
     * @return mixed A MultiBackend instance.
     */
    protected function getOrQueryRewriter(): OrQueryRewriter
    {
        return new OrQueryRewriter();
    }
}
