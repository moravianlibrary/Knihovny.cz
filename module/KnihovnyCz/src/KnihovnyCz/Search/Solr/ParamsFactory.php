<?php

namespace KnihovnyCz\Search\Solr;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Container\Exception\ContainerException;

/**
 * Factory for Solr search params objects.
 *
 * @category VuFind
 * @package  Search_Solr
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ParamsFactory extends \VuFind\Search\Params\ParamsFactory
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
            throw new \Exception('Unexpected options sent to factory.');
        }
        $helper = $container
            ->get(\VuFind\Search\Solr\HierarchicalFacetHelper::class);
        $parser = $container->get(\KnihovnyCz\Geo\Parser::class);
        $dateConverter = $container->get(\KnihovnyCz\Date\Converter::class);
        return parent::__invoke(
            $container,
            $requestedName,
            [
                $helper,
                $parser,
                $dateConverter,
            ]
        );
    }
}
