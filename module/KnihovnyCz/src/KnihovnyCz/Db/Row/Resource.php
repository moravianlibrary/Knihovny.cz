<?php
declare(strict_types=1);

/**
 * Class Resource
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
 * @package  KnihovnyCz\Db\Row
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Db\Row;

use VuFind\Db\Row\Resource as Base;

/**
 * Class Resource
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 * @see      \VuFind\Db\Table\GatewayFactory::getRowPrototype()
 *
 * @property ?string $author_sort
 */
class Resource extends Base
{
    /**
     * Use a record driver to assign metadata to the current row.  Return the
     * current object to allow fluent interface.
     *
     * @param \VuFind\RecordDriver\AbstractBase $driver    The record driver
     * @param \VuFind\Date\Converter            $converter Date converter
     *
     * @return \VuFind\Db\Row\Resource
     */
    public function assignMetadata($driver, \VuFind\Date\Converter $converter)
    {
        $result = parent::assignMetadata($driver, $converter);

        // Try to find an author for sorting; if not available, just leave
        // the default null:
        $authorSort = $driver->tryMethod('getPrimaryAuthorForSorting');
        if ($authorSort != null) {
            $authorSort = mb_substr($authorSort, 0, 255, "UTF-8");
            $this->author_sort = $authorSort;
        }

        return $result;
    }
}
