<?php

namespace KnihovnyCz\AjaxHandler;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Factory for AbstractIlsAndUserAction AJAX handlers.
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class AbstractIlsAndUserActionFactory implements FactoryInterface
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
        return new $requestedName(
            $container->get(\VuFind\Session\Settings::class),
            $container->get(\KnihovnyCz\ILS\MultiConnection::class),
            $container->get(\VuFind\Auth\ILSAuthenticator::class),
            $container->get(\VuFind\Auth\Manager::class)->getUserObject(),
            ...($options ?: [])
        );
    }
}
