<?php

namespace KnihovnyCz\Config;

use KnihovnyCz\Db\Table\Config as ConfigTable;
use Laminas\Config\Config;
use Psr\Container\ContainerInterface;

/**
 * Class PluginFactory
 *
 * @category VuFind
 * @package  KnihovnyCz\Config
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class PluginFactory extends \VuFind\Config\PluginFactory
{
    /**
     * Configuration table
     *
     * @var ConfigTable
     */
    protected $configTable;

    /**
     * Create a service for the specified name.
     *
     * @param ContainerInterface $container     Service container
     * @param string             $requestedName Name of service
     * @param array              $options       Options (unused)
     *
     * @return Config
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        // There is settings to database connection in main file, so it could not be
        // loaded from database
        if ($requestedName === 'config') {
            $dbConfig = new Config([]);
        } else {
            $this->configTable = $container
                ->get(\VuFind\Db\Table\PluginManager::class)
                ->get(ConfigTable::class);
            $dbConfig = $this->loadConfigFromDb($requestedName);
        }
        $pathResolver = $container->get(\VuFind\Config\PathResolver::class);
        $fileConfig = $this->loadConfigFile(
            $pathResolver->getConfigPath($requestedName . '.ini')
        );
        return $fileConfig->merge($dbConfig);
    }

    /**
     * Load the specified configuration file from database
     *
     * @param string $filename config file name
     *
     * @return Config
     */
    protected function loadConfigFromDb(string $filename)
    {
        return $this->configTable->getConfigByFile($filename);
    }
}
