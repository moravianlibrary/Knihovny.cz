<?php

namespace KnihovnyCz\Config;

use KnihovnyCz\Db\Table\Config as ConfigTable;
use Laminas\Config\Config as LaminasConfig;
use Psr\Container\ContainerInterface;
use VuFind\Config\Config as VuFindConfig;

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
     * List of configuration services that can be loaded from a database
     *
     * @var array|string[]
     */
    protected static $DATABASE_CONFIGS = [
        'content',
        'searches',
    ];

    /**
     * Configuration table
     *
     * @var ConfigTable
     */
    protected ConfigTable $configTable;

    /**
     * Create a service for the specified name.
     *
     * @param ContainerInterface $container     Service container
     * @param string             $requestedName Name of service
     * @param array              $options       Options (unused)
     *
     * @return VuFindConfig
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ) {
        $dbConfig = new LaminasConfig([]);
        // There is settings to database connection in main file, so it could not be loaded from
        if (in_array($requestedName, self::$DATABASE_CONFIGS)) {
            $this->configTable = $container->get(\VuFind\Db\Table\PluginManager::class)->get(ConfigTable::class);
            $dbConfig = $this->loadConfigFromDb($requestedName);
        }
        $fileConfig = parent::__invoke($container, $requestedName, $options);
        $fileLaminasConfig = new LaminasConfig($fileConfig->toArray());
        $mergedConfig = $fileLaminasConfig->merge($dbConfig);
        return new VuFindConfig($mergedConfig->toArray());
    }

    /**
     * Load the specified configuration file from database
     *
     * @param string $filename config file name
     *
     * @return LaminasConfig
     */
    protected function loadConfigFromDb(string $filename): LaminasConfig
    {
        return $this->configTable->getConfigByFile($filename);
    }
}
