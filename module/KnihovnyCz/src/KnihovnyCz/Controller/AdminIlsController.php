<?php

/**
 * Class Admin ILS driver controller
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
 * @category VuFind
 * @package  KnihovnyCz\Admin
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Controller;

/**
 * Class Admin ILS drivers controller
 *
 * @category VuFind
 * @package  KnihovnyCz\Admin
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AdminIlsController extends \VuFindAdmin\Controller\AbstractAdmin
{
    /**
     * Status action
     *
     * @return mixed
     */
    public function statusAction()
    {
        $config = $this->getConfig('MultiBackend')->Drivers;
        $config = array_filter(
            $config->toArray(),
            function ($driver) {
                return $driver !== 'NoILS';
            }
        );
        $view = $this->createViewModel(['drivers' => $config]);
        $view->setTemplate('admin/ils/status');
        return $view;
    }
}
