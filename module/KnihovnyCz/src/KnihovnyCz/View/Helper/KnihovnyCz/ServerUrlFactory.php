<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * ServerUrl helper factory.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ServerUrlFactory implements FactoryInterface
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
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        $cfg = $container->get(\VuFind\Config\PluginManager::class)->get('config');
        $helper = new $requestedName();
        if ($cfg->Site->reverse_proxy ?? false) {
            $helper->setUseProxy(true);
        }
        $url = $cfg->Site->url ?? null;
        if ($url != null && str_starts_with($url, 'https://')) {
            $helper->setScheme('https');
        }
        return $helper;
    }
}
