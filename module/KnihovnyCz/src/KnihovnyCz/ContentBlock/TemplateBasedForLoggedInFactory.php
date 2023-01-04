<?php
declare(strict_types=1);

/**
 * Class TemplateBasedForLoggedInFactory
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2022.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ContentBlock
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\ContentBlock;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class TemplateBasedForLoggedInFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ContentBlock
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class TemplateBasedForLoggedInFactory implements FactoryInterface
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

        $user = $container->get(\KnihovnyCz\Auth\Manager::class);

        $pageContent = $container->get(\VuFind\Content\PageLocator::class);
        $contentBlock = new $requestedName($pageContent, (bool)$user->isLoggedIn());

        // Populate cache storage if a setCacheStorage method is present:
        if (method_exists($contentBlock, 'setCacheStorage')) {
            $contentBlock->setCacheStorage(
                $container->get(\VuFind\Cache\Manager::class)->getCache('object')
            );
        }

        return $contentBlock;
    }
}
