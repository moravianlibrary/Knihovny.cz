<?php
declare(strict_types=1);

/**
 * Class ZiskejBase
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
 * @package  KnihovnyCz\RecordTab
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\RecordTab;

/**
 * Class ZiskejBase
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordTab
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
abstract class ZiskejBase extends \VuFind\RecordTab\AbstractBase
{
    protected bool $isZiskejActive = false;

    /**
     * Return whether Ziskej is active
     *
     * @return bool
     */
    public function isZiskejActive(): bool
    {
        return $this->isZiskejActive;
    }

    /**
     * Get server name
     *
     * @return string
     */
    public function getServerName(): string
    {
        /**
         * Http request object
         *
         * @var \Laminas\Http\PhpEnvironment\Request|null $request
         */
        $request = $this->getRequest();
        return is_object($request)
            ? ($request->getServer()->SERVER_NAME ?? '')
            : '';
    }

    /**
     * Get entity id
     *
     * @return string
     */
    public function getEntityId(): string
    {
        /**
         * Http request object
         *
         * @var \Laminas\Http\PhpEnvironment\Request|null $request
         */
        $request = $this->getRequest();
        return is_object($request)
            ? ($request->getServer('Shib-Identity-Provider') ?: '')
            : '';
    }

    /**
     * Get deduplicated record ids
     *
     * @return string[]
     */
    public function getDedupedRecordIds(): array
    {
        return $this->driver->tryMethod('getDeduplicatedRecordIds', [], []);
    }
}
