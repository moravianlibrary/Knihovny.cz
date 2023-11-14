<?php

declare(strict_types=1);

namespace KnihovnyCz\Search\Solr\Backend;

use Parle\Lexer;
use Parle\LexerException;
use Parle\Token;
use VuFind\Log\LoggerAwareTrait;

/**
 * Class OrQueryRewriter
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class OrQueryRewriter
{
    use LoggerAwareTrait;

    public const TERM = 1;
    public const OPERATOR_OR = 2;
    public const OPERATOR_AND = 3;
    public const OPERATOR_NOT = 4;
    public const SPACE = 5;
    public const BRACKET_OPEN = 6;
    public const BRACKET_CLOSE = 7;

    public const RANGE_QUERY = 8;

    protected ?Lexer $lexer = null;

    /**
     * Rewrite query
     *
     * @param string $query query to rewrite
     *
     * @return string $query rewritten query or original query
     */
    public function tryRewrite(string $query): string
    {
        try {
            return $this->rewrite($query);
        } catch (LexerException $le) {
            $this->logError("Query '$query' was not rewritten", ['exception' => $le]);
            return $query;
        }
    }

    /**
     * Rewrite query
     *
     * @param string $query query to rewrite
     *
     * @return string rewritten query
     */
    public function rewrite(string $query): string
    {
        $before = $query;
        $lexer = $this->getLexer();
        $lexer->consume($query);
        $stack = [
            [
                'result' => '',
                'first'  => true,
            ],
        ];
        do {
            $lexer->advance();
            $tok = $lexer->getToken();
            if (Token::UNKNOWN == $tok->id) {
                throw new LexerException("Unknown token '{$tok->value}' at offset $lexer->marker.");
            }
            $context = array_pop($stack);
            $result = $context['result'];
            if (self::BRACKET_OPEN == $tok->id) {
                $stack[] = $context;
                $context = [
                    'result' => '',
                    'first'  => true,
                ];
                $result = '';
            } elseif (self::BRACKET_CLOSE == $tok->id) {
                $topContext = $context;
                $topResult = trim($topContext['result']);
                if (!$topContext['first']) {
                    $topResult .= ')';
                }
                if (empty($stack)) {
                    throw new LexerException("Superfluous closing bracket at offset $lexer->marker.");
                }
                $context = array_pop($stack);
                $result = $context['result'];
                $result = $result . '(' . trim($topResult) . ')';
            } elseif (self::OPERATOR_OR == $tok->id) {
                if ($context['first']) {
                    $context['first'] = false;
                    $result = '(' . trim($result) . ') ';
                    $result = trim($result) . ' OR (';
                } else {
                    $result = trim($result) . ') OR (';
                }
            } elseif (!(self::SPACE == $tok->id && str_ends_with($result, '('))) {
                $result .= $tok->value;
            }
            $context['result'] = $result;
            $stack[] = $context;
        } while (Token::EOI != $tok->id);

        $context = array_pop($stack);
        if (!empty($stack)) {
            throw new LexerException('Superfluous opening brackets.');
        }
        $result = $context['result'];
        if (!$context['first']) {
            $result .= ')';
        }
        $this->debug("Query was rewritten from '$before' to '$result'");
        return $result;
    }

    /**
     * Get lexer for Solr queries
     *
     * @return \Parle\Lexer
     */
    protected function getLexer(): Lexer
    {
        if ($this->lexer !== null) {
            return $this->lexer;
        }
        $lex = new Lexer();
        $lex->push('OR', self::OPERATOR_OR);
        $lex->push('AND', self::OPERATOR_AND);
        $lex->push('NOT', self::OPERATOR_NOT);
        $lex->push("\(", self::BRACKET_OPEN);
        $lex->push("\)", self::BRACKET_CLOSE);
        // "phrase search"
        $lex->push('\\"[^\\"]+\\"', self::TERM);
        // field:"phrase search"
        $lex->push("[\p{L}|\\_]+:\\\"[^\\\"]+\\\"", self::TERM);
        // word
        $lex->push("[\p{L}|\p{N}|\p{Po}|\p{Pd}|\\_|~|+|\\^]+", self::TERM);
        // field:word
        $lex->push("[\p{L}|\\_]+:[\p{L}|\p{N}|\p{Po}|\p{Pd}|~|+|\\^]+", self::TERM);
        // range query
        $lex->push("\\{[\w|d]+ TO [\w|d]+\\}", self::RANGE_QUERY);
        $lex->push("\\[[\w|d]+ TO [\w|d]+\\]", self::RANGE_QUERY);
        // field:ranqe qeuery
        $lex->push("[\p{L}|\\_]+:\\{[\w|d]+ TO [\w|d]+\\}", self::RANGE_QUERY);
        $lex->push("[\p{L}|\\_]+:\\[[\w|d]+ TO [\w|d]+\\]", self::RANGE_QUERY);
        // space
        $lex->push("\s+", self::SPACE);
        $lex->build();
        $this->lexer = $lex;
        return $this->lexer;
    }
}
