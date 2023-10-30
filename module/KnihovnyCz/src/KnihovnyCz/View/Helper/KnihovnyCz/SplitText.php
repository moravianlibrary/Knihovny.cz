<?php

declare(strict_types=1);

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
