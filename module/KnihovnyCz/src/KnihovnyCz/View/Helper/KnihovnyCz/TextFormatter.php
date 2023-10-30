<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;
use VuFind\RecordDriver\AbstractBase;

/**
 * Class TextFormatter
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class TextFormatter extends AbstractHelper
{
    /**
     * Record driver
     *
     * @var AbstractBase
     */
    protected $driver;

    /**
     * Store a record driver object and return this object so that the appropriate
     * template can be rendered.
     *
     * @param AbstractBase $driver Record driver object.
     *
     * @return TextFormatter This helper
     */
    public function __invoke(AbstractBase $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Format array
     *
     * @param string $label    Label for data
     * @param string $method   Driver method to get data
     * @param string $arrayKey Key of array returned by metho
     * @param bool   $flatten  Whether to flatten an array by one level
     * @param bool   $implode  Implode data instead of repeating lines
     *
     * @return string Formatted text
     */
    public function formatArray(
        string $label,
        string $method,
        string $arrayKey = '',
        bool $flatten = false,
        bool $implode = false
    ): string {
        $transEsc = $this->getView()->plugin('transEsc');
        $data = $this->driver->tryMethod($method);
        if (is_array($data)) {
            if ($flatten) {
                $data = array_map(
                    function ($item) {
                        return $item[0];
                    },
                    $data
                );
            }
            if ($implode) {
                return $transEsc($label) . ': ' . implode($data) . "\r\n";
            }
            $text = '';
            foreach ($data as $item) {
                $value = empty($arrayKey) ? $item : $item[$arrayKey];
                $text .= $transEsc($label) . ": $value\r\n";
            }
            return $text;
        }
        return '';
    }

    /**
     * Format string
     *
     * @param string $label  Label for data
     * @param string $method Driver method to get data
     *
     * @return string Formatted text
     */
    public function formatString(string $label, string $method): string
    {
        $transEsc = $this->getView()->plugin('transEsc');
        $data = $this->driver->tryMethod($method);
        return empty($data) ? '' : $transEsc($label) . ": $data\r\n";
    }
}
