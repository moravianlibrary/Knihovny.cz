<?php

declare(strict_types=1);

namespace KnihovnyCz\Ziskej;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

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
     * @param ContainerInterface $container     DI container
     * @param string             $requestedName Service name
     * @param array|null         $options       Service options
     *
     * @return \KnihovnyCz\Ziskej\Ziskej
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ): Ziskej {
        /**
         * Main configuration
         *
         * @var \Vufind\Config\Config $config
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
