<?php

/**
 * Context Help Helper
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
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

/**
 * Context Help Helper
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ContextHelp extends AbstractHelper
{
    /**
     * Render context help
     *
     * @param string $page Context help page identifier
     * @param string $type Context help type
     *
     * @return string
     */
    public function __invoke(string $page, string $type = ''): string
    {
        $classes[] = 'context-help-link';

        if ($type !== '') {
            $classes[] = 'context-help-link-' . $type;
        }

        $view = $this->getView();
        return isset($view) ? $view->render(
            'Helpers/context-help.phtml',
            [
                'page' => $page,
                'classes' => implode(' ', $classes),
            ]
        ) : '';
    }
}
