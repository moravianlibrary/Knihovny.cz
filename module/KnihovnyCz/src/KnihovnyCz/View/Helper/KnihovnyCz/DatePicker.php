<?php

/**
 * View helper for date picker.
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
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for date picker.
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class DatePicker extends AbstractHelper
{
    protected bool $initialized = false;

    /**
     * Render context help
     *
     * @param string? $name form element name
     *
     * @return string
     */
    public function __invoke(string $name = null): string
    {
        $result = $this->getView()->render('Helpers/date-picker.phtml', [
            'formName' => $name,
            'initialized' => $this->initialized,
        ]);
        $this->initialized = true;
        return $result;
    }
}
