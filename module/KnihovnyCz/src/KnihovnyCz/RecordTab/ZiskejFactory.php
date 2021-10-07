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

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class ZiskejFactory
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param \Interop\Container\ContainerInterface $container
     * @param string                                $requestedName
     * @param array|null                            $options
     *
     * @return \KnihovnyCz\RecordTab\Ziskej
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): Ziskej {
        return new $requestedName(
            $container->get(\VuFind\Auth\Manager::class),
            $container->get(\VuFind\ILS\Connection::class),
            $container->get(\Mzk\ZiskejApi\Api::class),
            $container->get(\KnihovnyCz\Ziskej\ZiskejMvs::class)
        );
    }
}
