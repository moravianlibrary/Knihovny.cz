<?php

/**
 * View helper to work with user cards
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

use Laminas\Db\ResultSet\AbstractResultSet;
use Laminas\View\Helper\AbstractHelper;

/**
 * View helper to work with user cards
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserCards extends AbstractHelper
{
    /**
     * User cards
     *
     * @var \Laminas\Db\ResultSet\AbstractResultSet
     */
    private AbstractResultSet $cards;

    /**
     * Invoke function
     *
     * @param AbstractResultSet $cards User cards
     *
     * @return $this
     */
    public function __invoke(AbstractResultSet $cards): self
    {
        $this->cards = $cards;
        return $this;
    }

    /**
     * Get user cards sorted by library name
     *
     * @return \KnihovnyCz\Db\Row\UserCard[]
     */
    public function getSortedByLibraryName(): array
    {
        $return = [];
        $index = 0;
        foreach ($this->cards as $card) {
            $return[$this->getView()->translate(
                'Source::' . $card['card_name']
            ) . ' ' . $index] = $card;
            $index++;
        }

        uksort(
            $return,
            function (string $a, string $b): int {
                return $this->getView()->sorter()->compare($a, $b);
            }
        );

        return $return;
    }
}
