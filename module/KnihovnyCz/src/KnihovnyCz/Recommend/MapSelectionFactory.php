<?php

declare(strict_types=1);

namespace KnihovnyCz\Recommend;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class MapSelectionFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Recommend
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class MapSelectionFactory
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException&\Throwable if any other error occurs
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options passed to factory.');
        }

        // add basemap options
        $basemapConfig = $container->get(\VuFind\GeoFeatures\BasemapConfig::class);
        $basemapOptions = $basemapConfig->getBasemap('MapSelection');

        // get MapSelection options
        $mapSelectionConfig
            = $container->get(\VuFind\GeoFeatures\MapSelectionConfig::class);
        $mapSelectionOptions = $mapSelectionConfig->getMapSelectionOptions();

        $search = $container->get(\VuFindSearch\Service::class);
        $parser = $container->get(\KnihovnyCz\Geo\Parser::class);
        return new $requestedName(
            $search,
            $basemapOptions,
            $mapSelectionOptions,
            $parser
        );
    }
}
