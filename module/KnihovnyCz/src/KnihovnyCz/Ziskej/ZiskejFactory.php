<?php
declare(strict_types=1);
/**
 * Class ZiskejApiFactory
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
 * @package  KnihovnyCz\Ziskej
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Ziskej;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Factory for instantiating objects
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Ziskej
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param \Interop\Container\ContainerInterface $container     DI container
     * @param string                                $requestedName Service name
     * @param array|null                            $options       Service options
     *
     * @return \KnihovnyCz\Ziskej\Ziskej
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): Ziskej {
        /**
         * Main configuration
         *
         * @var \Laminas\Config\Config $config
         */
        $config = $container->get('VuFind\Config')->get('config');

        /**
         * Cookie manager
         *
         * @var \VuFind\Cookie\CookieManager $cookieManager
         */
        $cookieManager = $container->get('VuFind\CookieManager');

        return new $requestedName($config, $cookieManager);
    }
}
