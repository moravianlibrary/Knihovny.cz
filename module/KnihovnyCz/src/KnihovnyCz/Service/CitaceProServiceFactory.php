<?php

declare(strict_types=1);

namespace KnihovnyCz\Service;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Container;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class CitaceProServiceFactory
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class CitaceProServiceFactory implements FactoryInterface
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
        ?array $options = null
    ) {
        $config = $container
            ->get(\VuFind\Config\PluginManager::class)
            ->get('citation');
        $defaultCitationStyle = $config->Citation->default_citation_style;
        $session = new Container(
            'Account',
            $container->get(\Laminas\Session\SessionManager::class)
        );
        $defaultCitationStyle = isset($session->citationStyle)
            && !empty($session->citationStyle) ? $session->citationStyle
            : $defaultCitationStyle;
        $recordLoader = $container->get(\VuFind\Record\Loader::class);
        return new $requestedName($config, (string)$defaultCitationStyle, $recordLoader);
    }
}
