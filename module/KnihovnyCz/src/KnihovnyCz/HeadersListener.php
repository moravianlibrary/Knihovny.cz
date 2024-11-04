<?php

declare(strict_types=1);

namespace KnihovnyCz;

use Laminas\EventManager\EventInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\ListenerAggregateTrait;
use Laminas\Mvc\MvcEvent;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * HeadersListener
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class HeadersListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * Attach one or more listeners
     *
     * @param EventManagerInterface $events   Event
     * @param int                   $priority Priority
     *
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[]
            = $events->attach(MvcEvent::EVENT_RENDER, [$this, 'addHeaders'], 2);
    }

    /**
     * Add HTTP response headers based on route name
     *
     * @param EventInterface $e Event
     *
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function addHeaders(EventInterface $e): void
    {
        $app = $e->getApplication();
        $mvcEvent = $app->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        if (!$routeMatch) {
            return;
        }

        $matchedRouteName = $routeMatch->getMatchedRouteName();
        $config = $app->getServiceManager()->get('Config');
        $responseHeaders = $app->getResponse()->getHeaders();
        if (!$responseHeaders) {
            return;
        }

        foreach ($config['http']['headers'] ?? [] as $key => $headerValues) {
            if ($key === '*' || $key === $matchedRouteName) {
                $responseHeaders->addHeaders($headerValues);
            }
        }
    }
}
