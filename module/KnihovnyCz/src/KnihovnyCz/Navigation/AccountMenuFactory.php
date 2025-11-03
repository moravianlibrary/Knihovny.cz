<?php

namespace KnihovnyCz\Navigation;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;
use VuFind\Navigation\AbstractMenuFactory;

/**
 * Account menu factory
 *
 * @category VuFind
 * @package  Navigation
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class AccountMenuFactory extends AbstractMenuFactory
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
        $configManager = $container->get(\VuFind\Config\PluginManager::class);
        // Only load the connector if we need to show
        $overdriveConfig = $configManager->get('Overdrive');
        $connector = null;
        if ($overdriveConfig->Overdrive->showMyContent != 'never') {
            $connector = $container->get(
                \VuFind\DigitalContent\OverdriveConnector::class
            );
        }

        $mainConfig = $configManager->get('config');

        return parent::__invoke(
            $container,
            $requestedName,
            [
                'AccountMenu.yaml',
                $container->get(\VuFind\Config\AccountCapabilities::class),
                $container->get(\VuFind\Auth\Manager::class),
                $container->get(\VuFind\ILS\Connection::class),
                $container->get(\VuFind\Auth\ILSAuthenticator::class),
                $connector,
                $container->get(\KnihovnyCz\Ziskej\ZiskejMvs::class),
                $container->get(\KnihovnyCz\Ziskej\ZiskejEdd::class),
                $mainConfig->Catalog->toArray() ?? [],
                $container->get(\KnihovnyCz\Service\PalmknihyApiService::class),
            ]
        );
    }
}
