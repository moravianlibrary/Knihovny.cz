<?php

/**
 * Class SplitText
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

use function strlen;

/**
 * Class SplitText
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SplitText extends AbstractHelper
{
    private ?string $first;

    private ?string $last;

    /**
     * Invoke function
     *
     * @param string $string String to split
     * @param int    $length Length of first part
     *
     * @return $this
     */
    public function __invoke(string $string = '', int $length = 0): self
    {
        $length = (int)min(strlen($string), $length);
        $strpos = (int)strpos($string, ' ', $length);

        if ($strpos < $length) {
            $this->first = $string;
            $this->last = null;
        } else {
            $this->first = substr($string, 0, $strpos) ?: null;
            $this->last = substr($string, $strpos, strlen($string) - $strpos)
                ?: null;
        }

        return $this;
    }

    /**
     * Return first part of text
     *
     * @return string|null
     */
    public function first(): ?string
    {
        return $this->first;
    }

    /**
     * Return last part of text
     *
     * @return string|null
     */
    public function last(): ?string
    {
        return $this->last;
    }
}
