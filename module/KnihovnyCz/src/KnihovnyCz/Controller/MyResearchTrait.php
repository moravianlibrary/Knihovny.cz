<?php

/**
 * Class MyResearchTrait
 *
 * PHP version 7
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
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

/**
 * Class MyResearchTrait
 *
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait MyResearchTrait
{
    protected static $EXCEPTIONS_TO_SHOW = [
        'VuFind\ILS\Driver\AlephRestfulException',
        'VuFind\Exception\ILS'
    ];

    /**
     * Show the exception in the flash messenger
     *
     * @param \Exception $ex exception to show
     *
     * @return void
     */
    protected function showException(\Exception $ex)
    {
        $message = 'ils_offline_home_message';
        if (in_array(get_class($ex), self::$EXCEPTIONS_TO_SHOW)) {
            $message = $ex->getMessage();
        }
        $this->flashMessenger()->addErrorMessage($message);
    }
}
