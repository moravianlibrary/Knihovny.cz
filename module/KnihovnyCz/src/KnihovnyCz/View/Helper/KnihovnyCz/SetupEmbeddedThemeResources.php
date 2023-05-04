<?php

/**
 * Setup Embedded Theme Resources
 *
 * PHP version 8
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
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;
use VuFindTheme\ResourceContainer;

/**
 * Setup Embedded Theme Resources
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SetupEmbeddedThemeResources extends AbstractHelper
{
    /**
     * Theme resource container
     *
     * @var \VuFindTheme\ResourceContainer
     */
    protected ResourceContainer $container;

    /**
     * Constructor
     *
     * @param \VuFindTheme\ResourceContainer $container Theme resource container
     */
    public function __construct(ResourceContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Set up items based on contents of theme resource container.
     *
     * @return void
     */
    public function __invoke(): void
    {
        $this->addLinks();
    }

    /**
     * Add links to header.
     *
     * @return void
     */
    protected function addLinks(): void
    {
        $headLink = $this->getView()->plugin('headLink');

        $favicon = $this->container->getFavicon();
        if (!empty($favicon)) {
            $imageLink = $this->getView()->plugin('imageLink');
            $headLink(
                [
                    'href' => $imageLink($favicon),
                    'type' => 'image/x-icon', 'rel' => 'shortcut icon',
                ]
            );
        }
    }
}
