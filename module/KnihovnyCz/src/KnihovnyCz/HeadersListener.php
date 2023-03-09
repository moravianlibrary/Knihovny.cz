<?php
declare(strict_types = 1);

/**
 * Class HeadersListener
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2023.
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
 * @package  KnihovnyCz
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
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
        $matchedRouteName = $mvcEvent->getRouteMatch()->getMatchedRouteName();
        $config = $app->getServiceManager()->get('Config');

        $responseHeaders = $app->getResponse()->getHeaders();
        foreach ($config['http']['headers'] ?? [] as $key => $headerValues) {
            if ($key === '*') {
                $responseHeaders->addHeaders($headerValues);
            } else {
                if ($key === $matchedRouteName) {
                    $responseHeaders->addHeaders($headerValues);
                }
            }
        }
    }
}
