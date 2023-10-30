<?php

namespace KnihovnyCz\Sitemap\Plugin;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use VuFind\Sitemap\Plugin\IndexFactory as Base;

/**
 * Index-based generator plugin factory
 *
 * @category KnihovnyCz
 * @package  Sitemap
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class IndexFactory extends Base
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
        if (!empty($options)) {
            throw new \Exception('Unexpected options passed to factory.');
        }
        $configLoader = $container->get(\VuFind\Config\PluginManager::class);
        $sitemapConfig = $configLoader->get('sitemap');
        $retrievalMode = $sitemapConfig->Sitemap->retrievalMode ?? 'search';
        return new $requestedName(
            $this->getBackendSettings($sitemapConfig),
            $this->getIdFetcher($container, $retrievalMode),
            $sitemapConfig->Sitemap->countPerPage ?? 10000,
            (array)($sitemapConfig->Sitemap->extraFilters ?? []),
            $tab = $sitemapConfig->Sitemap->tab ?? 'DedupedRecord'
        );
    }
}
