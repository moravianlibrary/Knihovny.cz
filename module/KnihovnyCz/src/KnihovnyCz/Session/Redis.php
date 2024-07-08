<?php

/**
 * Redis session handler
 *
 * Note: Using phpredis extension (see https://github.com/phpredis/phpredis) is
 * optional, this class use Credis in standalone mode by default
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2019.
 * Copyright (C) The National Library of Finland 2023.
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
 * @package  Session_Handlers
 * @author   Veros Kaplan <cpk-dev@mzk.cz>
 * @author   Josef Moravec <moravec@mzk.cz>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:session_handlers Wiki
 */

namespace KnihovnyCz\Session;

use VuFind\Session\Redis as Base;

/**
 * Redis session handler
 *
 * @category VuFind
 * @package  Session_Handlers
 * @author   Veros Kaplan <cpk-dev@mzk.cz>
 * @author   Josef Moravec <moravec@mzk.cz>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:session_handlers Wiki
 */
class Redis extends Base
{
}
