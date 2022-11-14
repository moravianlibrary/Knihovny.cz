<?php
declare(strict_types=1);

/**
 * Class Highlighter
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
            $this->patterns[] = "/(^|\s+)(${part})/ui";
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
