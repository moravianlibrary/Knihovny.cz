<?php

declare(strict_types=1);

namespace KnihovnyCz\ContentBlock;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class TemplateBasedFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class TemplateBasedFactory implements FactoryInterface
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
        $pageContent = $container->get(\VuFind\Content\PageLocator::class);
        $contentBlock = new $requestedName($pageContent);

        // Populate cache storage if a setCacheStorage method is present:
        if (method_exists($contentBlock, 'setCacheStorage')) {
            $contentBlock->setCacheStorage(
                $container->get(\VuFind\Cache\Manager::class)->getCache('object')
            );
        }

        return $contentBlock;
    }
}
