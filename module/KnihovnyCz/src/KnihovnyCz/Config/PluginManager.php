<?php

namespace KnihovnyCz\Config;

use Laminas\ServiceManager\AbstractPluginManager;

/**
 * Class PluginManager
 *
 * @category VuFind
 * @package  KnihovnyCz\Config
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class PluginManager extends \VuFind\Config\PluginManager
{
    /**
     * Constructor
     *
     * Make sure plugins are properly initialized.
     *
     * @param mixed $configOrContainerInstance Configuration or container instance
     * @param array $v3config                  If $configOrContainerInstance is a
     * container, this value will be passed to the parent constructor.
     */
    public function __construct(
        $configOrContainerInstance = null,
        array $v3config = []
    ) {
        // We need to skip parent constructor, this is constructor from Laminas
        AbstractPluginManager::__construct($configOrContainerInstance, $v3config);
    }
}
