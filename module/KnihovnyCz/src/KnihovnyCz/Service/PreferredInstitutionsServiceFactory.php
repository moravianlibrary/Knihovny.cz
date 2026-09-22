<?php

declare(strict_types=1);

namespace KnihovnyCz\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory for InstitutionMappingsService.
 *
 * @category VuFind
 * @package  Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class PreferredInstitutionsServiceFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return PreferredInstitutionsService
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ): PreferredInstitutionsService {
        $config = $container->get(\VuFind\Config\PluginManager::class);
        $facetConfig = $config->get('facets');
        $mappings = $facetConfig->InstitutionsMappings->toArray() ?? [];
        $searchConfig = $config->get('searches');
        $institutionField = $searchConfig->Records->institution_field;
        $user = $container->get('VuFind\AuthManager')->getUserObject() ?? null;
        return new PreferredInstitutionsService($user, $mappings, $institutionField);
    }
}
