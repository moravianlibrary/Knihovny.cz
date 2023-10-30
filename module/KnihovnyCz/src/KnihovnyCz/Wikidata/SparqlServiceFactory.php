<?php

declare(strict_types=1);

namespace KnihovnyCz\Wikidata;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class SparqlServiceFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Wikidata
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SparqlServiceFactory
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
     * @throws ContainerException if any other error occurs
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options passed to factory.');
        }
        $generator = $container
            ->get(\VuFind\Config\PluginManager::class)
            ->get('config')->Site->generator;
        $version = preg_replace('/(.+) (.+) (\(.*\))/', '$2', $generator);
        return new $requestedName(
            $container->get(\KnihovnyCz\Service\GuzzleHttpService::class),
            $version,
            \VuFind\Config\Version::getBuildVersion()
        );
    }
}
