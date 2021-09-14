<?php
declare(strict_types=1);
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
namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

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
    private ?string $_first;

    private ?string $_last;

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

        if ($strpos <= $length) {
            $this->_first = $string;
            $this->_last = null;
        } else {
            $strpos = (int)strpos($string, ' ', $length);
            $this->_first = substr($string, 0, $strpos) ?: null;
            $this->_last = substr($string, $strpos, strlen($string) - $strpos)
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
        return $this->_first;
    }

    /**
     * Return last part of text
     *
     * @return string|null
     */
    public function last(): ?string
    {
        return $this->_last;
    }
}
