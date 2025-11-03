<?php

declare(strict_types=1);

namespace KnihovnyCz\Autocomplete;

/**
 * Class Highlighter
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\View_Helpers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Highlighter
{
    /**
     * Replacement to use for highlighting
     */
    protected const HIGHLIGHT_REPLACEMENT = '$1<b>$2</b>';

    /**
     * Search phrases to use for highlighting
     *
     * @var string
     */
    protected $patterns = [];

    /**
     * Constructor
     *
     * @param string $phrase phrase to use for highlighting
     */
    public function __construct($phrase)
    {
        $parts = (array)preg_split('/\s+/', trim($phrase));
        // match longer words first - they can have common prefix
        usort(
            $parts,
            function ($a, $b) {
                return strlen($b) <=> strlen($a);
            }
        );
        foreach ($parts as $part) {
            $part = preg_quote($part);
            $this->patterns[] = "/(^|\s+)({$part})/ui";
        }
    }

    /**
     * Highlight the result
     *
     * @param string $text text to highlight
     *
     * @return string highlighted result
     */
    public function highlight($text)
    {
        foreach ($this->patterns as $pattern) {
            $text = preg_replace($pattern, self::HIGHLIGHT_REPLACEMENT, $text);
        }
        return $text;
    }
}
