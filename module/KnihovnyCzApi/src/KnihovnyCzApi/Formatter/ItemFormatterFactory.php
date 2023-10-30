<?php

declare(strict_types=1);

namespace KnihovnyCzApi\Formatter;

/**
 * Class ItemFormatterFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCzApi\Formatter
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ItemFormatterFactory extends \VuFindApi\Formatter\RecordFormatterFactory
{
    /**
     * Record fields configuration file name
     *
     * @var string
     */
    protected $configFile = 'SearchApiItemFields.yaml';
}
