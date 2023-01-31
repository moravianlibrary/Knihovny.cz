<?php
declare(strict_types=1);

/**
 * Record tab Ziskej Edd
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
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\RecordTab;

/**
 * Record tab Ziskej Edd
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejEdd extends ZiskejBase
{
    private \KnihovnyCz\Ziskej\ZiskejEdd $_ziskejEdd;

    /**
     * Constructor
     *
     * @param \VuFind\Auth\Manager         $authManager Authentication manager
     * @param \VuFind\ILS\Connection       $ilsDriver   ILS driver
     * @param \Mzk\ZiskejApi\Api           $ziskejApi   Ziskej API connector
     * @param \KnihovnyCz\Ziskej\ZiskejEdd $ziskejEdd   Ziskej ILL model
     */
    public function __construct(
        \VuFind\Auth\Manager $authManager,
        \VuFind\ILS\Connection $ilsDriver,
        \Mzk\ZiskejApi\Api $ziskejApi,
        \KnihovnyCz\Ziskej\ZiskejEdd $ziskejEdd
    ) {
        $this->authManager = $authManager;
        $this->ilsDriver = $ilsDriver;
        $this->ziskejApi = $ziskejApi;
        $this->_ziskejEdd = $ziskejEdd;

        $this->isZiskejActive = $ziskejEdd->isEnabled();
    }

    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getDescription(): string
    {
        return 'tab_title_ziskej_edd';
    }

    /**
     * Is this tab visible?
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isActive(): bool
    {
        return $this->isZiskejActive()
            && $this->getRecordDriver()->tryMethod('getEddBoolean');
    }

    /**
     * Get ZiskejEdd class
     *
     * @return \KnihovnyCz\Ziskej\ZiskejEdd
     */
    public function getZiskejEdd(): \KnihovnyCz\Ziskej\ZiskejEdd
    {
        return $this->_ziskejEdd;
    }

    /**
     * Get ids of active libraries in Ziskej
     *
     * @return string[][]
     *
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getZiskejLibsIds(): array
    {
        return $this->convertLibsFromZiskej(
            $this->ziskejApi->getLibrariesEddActive()->getAll()
        );
    }
}
