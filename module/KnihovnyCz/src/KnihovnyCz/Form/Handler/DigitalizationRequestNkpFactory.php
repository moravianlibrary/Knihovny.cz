<?php

declare(strict_types=1);

namespace KnihovnyCz\Form\Handler;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Class DigitalizationRequestNkpFactory
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Form\Handler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class DigitalizationRequestNkpFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }

        return new $requestedName(
            $container->get('ViewRenderer'),
            $container->get(\VuFind\Config\PluginManager::class)->get('config'),
            $container->get(\VuFind\Mailer\Mailer::class),
            $container->get(\VuFind\Db\Service\PluginManager::class)
                ->get(\KnihovnyCz\Db\Service\NkpDigitalizationRequestsServiceInterface::class)
        );
    }
}
