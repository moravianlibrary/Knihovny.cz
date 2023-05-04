<?php

/**
 * Class EmbeddedController
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
 * @package  KnihovnyCz\Controller
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\Http\Header\ContentSecurityPolicy;

/**
 * Class EmbeddedController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
abstract class EmbeddedController extends SearchController
{
    /**
     * Prepare embedded layout
     *
     * @return void
     */
    protected function setLayout(): void
    {
        // Set main layout
        $this->layout('embedded/layout');

        // Disable session writes
        $this->disableSessionWrites();

        // Add value frame-ancestors * to Content-Security-Policy header
        $headers = $this->getResponse()->getHeaders();
        $cspHeader = $headers->get('Content-Security-Policy');
        if ($cspHeader === false) {
            $cspHeader = new ContentSecurityPolicy();
            $headers->addHeader($cspHeader);
        }
        if (is_iterable($cspHeader)) {
            $cspHeader = $cspHeader->current();
        }
        $cspHeader->setDirective('frame-ancestors', ['*']);
    }
}
