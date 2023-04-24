<?php

/**
 * Class ZiskejFactory
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2019.
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
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\RecordTab;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Class ZiskejEddFactory
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejEddFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     DI container
     * @param string             $requestedName Service name
     * @param array|null         $options       Service options
     *
     * @return \KnihovnyCz\RecordTab\ZiskejEdd
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): ZiskejEdd {
        return new $requestedName(
            $container->get(\VuFind\Auth\Manager::class),
            $container->get(\VuFind\ILS\Connection::class),
            $container->get(\Mzk\ZiskejApi\Api::class),
            $container->get(\KnihovnyCz\Ziskej\ZiskejEdd::class)
        );
    }
}
