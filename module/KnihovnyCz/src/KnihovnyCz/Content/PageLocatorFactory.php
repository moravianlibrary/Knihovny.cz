<?php

declare(strict_types=1);

namespace KnihovnyCz\Content;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;
use VuFind\I18n\Locale\LocaleSettings;

/**
 * Class PageLocatorFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Content
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class PageLocatorFactory extends \VuFind\Content\PageLocatorFactory
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
        array $options = null
    ) {
        if ($options !== null) {
            throw new \Exception('Unexpected options sent to factory!');
        }
        $settings = $container->get(LocaleSettings::class);
        $configManager = $container->get(\VuFind\Config\PluginManager::class);

        $locator =  new $requestedName(
            $container->get(\VuFindTheme\ThemeInfo::class),
            $settings->getUserLocale(),
            $settings->getDefaultLocale(),
            $configManager->get('content')->Repository->base_url ?? ''
        );

        // Populate cache storage if a setCacheStorage method is present:
        if (method_exists($locator, 'setCacheStorage')) {
            $locator->setCacheStorage(
                $container->get(\VuFind\Cache\Manager::class)->getCache('object')
            );
        }
        return $locator;
    }
}
