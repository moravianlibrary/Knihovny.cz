<?php

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
