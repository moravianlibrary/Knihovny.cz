<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

/**
 * Escape element id View Helper
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class EscapeElementId extends AbstractHelper
{
    public const SEARCH = [
        '-',
        '.',
        ',',
        '*',
        ':',
    ];

    public const REPLACE = '_';

    /**
     * Escape identifier for use as selector in HTML
     *
     * @param string $id identifier to escape
     *
     * @return string
     */
    public function __invoke(string $id): string
    {
        return str_replace(self::SEARCH, self::REPLACE, $id);
    }
}
