<?php

declare(strict_types=1);

namespace KnihovnyCz\AjaxHandler;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Mzk\ZiskejApi\Api;
use Psr\Container\ContainerInterface;

/**
 * Class Get Ziskej Edd Fee Factory
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Robert Sipek <robert.sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GetZiskejEddFeeFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param \Psr\Container\ContainerInterface $container     DI container
     * @param string                            $requestedName Service name
     * @param array|null                        $options       Service options
     *
     * @return \KnihovnyCz\AjaxHandler\GetZiskejEddFee
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): GetZiskejEddFee {
        return new $requestedName(
            $container->get(Api::class)
        );
    }
}
