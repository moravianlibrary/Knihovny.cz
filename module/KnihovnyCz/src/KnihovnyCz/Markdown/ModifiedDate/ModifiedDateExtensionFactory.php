<?php

declare(strict_types=1);

namespace KnihovnyCz\Markdown\ModifiedDate;

use KnihovnyCz\Content\GitLabService;
use KnihovnyCz\Content\PageLocator;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\View\Renderer\PhpRenderer;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class ModifiedDateExtensionFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Markdown\ModifiedDate
 * @author   Pavel Pátek <pavel.patek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ModifiedDateExtensionFactory
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
        return new $requestedName(
            $container->get(GitLabService::class),
            $container->get(PageLocator::class),
            $container->get(PhpRenderer::class)
        );
    }
}
