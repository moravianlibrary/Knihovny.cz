<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordDriver;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Class SolrLocalFactory
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SolrLocalFactory extends SolrDefaultFactory
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
     * @throws \Exception if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ) {
        /**
         * Record driver
         *
         * @var SolrLocal $driver
         */
        $driver = parent::__invoke($container, $requestedName, $options);
        $driver->attachCitaceProService(
            $container->get(\KnihovnyCz\Service\CitaceProService::class)
        );
        $driver->atttachSolrIdResolver(
            $container->get(\KnihovnyCz\ILS\Service\SolrIdResolver::class)
        );
        return $driver;
    }
}
