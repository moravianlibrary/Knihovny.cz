<?php

namespace KnihovnyCz\ILS\Driver;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;
use VuFind\ILS\Driver\DriverWithDateConverterFactory;

/**
 * Class AlephNkpFactory
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Driver
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AlephNkpFactory extends DriverWithDateConverterFactory
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
            throw new \Exception('Unexpected options passed to factory.');
        }
        return parent::__invoke(
            $container,
            $requestedName,
            [
                $container->get(\VuFind\Cache\Manager::class),
                $container->get(\Laminas\Mvc\I18n\Translator::class),
                $container->get(\KnihovnyCz\Service\TranslationService::class),
            ]
        );
    }
}
