<?php

declare(strict_types=1);

namespace KnihovnyCz\AjaxHandler;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class GetObalkyKnihCoverWithoutSolrFactory
 *
 * @category CPK-vufind-6
 * @package  KnihovnyCz\AjaxHandler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GetObalkyKnihCoverWithoutSolrFactory implements FactoryInterface
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
        array $options = null
    ) {
        $config
            = $container->get(\VuFind\Config\PluginManager::class)->get('config');
        $useFallbacks = (bool)$config->Content->useCoverFallbacksOnFail ?? false;
        return new $requestedName(
            $container->get(\VuFind\Session\Settings::class),
            $container->get(\VuFind\Content\Covers\PluginManager::class)
                ->get(\VuFind\Content\Covers\ObalkyKnih::class),
            // We only need the view renderer if we're going to use fallbacks:
            $useFallbacks ? $container->get('ViewRenderer') : null,
            $useFallbacks
        );
    }
}
