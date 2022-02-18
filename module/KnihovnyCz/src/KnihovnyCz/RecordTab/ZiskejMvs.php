<?php

/**
 * Record tab Ziskej
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
 * Record tab Ziskej
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejMvs extends ZiskejBase
{
    private \VuFind\Auth\Manager $_authManager;

    private \VuFind\ILS\Connection $_ilsDriver;

    private \Mzk\ZiskejApi\Api $_ziskejApi;

    private \KnihovnyCz\Ziskej\ZiskejMvs $_ziskejMvs;

    /**
     * Constructor
     *
     * @param \VuFind\Auth\Manager         $authManager Authentication manager
     * @param \VuFind\ILS\Connection       $ilsDriver   ILS driver
     * @param \Mzk\ZiskejApi\Api           $ziskejApi   Ziskej API connector
     * @param \KnihovnyCz\Ziskej\ZiskejMvs $ziskejMvs   Ziskej ILL model
     */
    public function __construct(
        \VuFind\Auth\Manager $authManager,
        \VuFind\ILS\Connection $ilsDriver,
        \Mzk\ZiskejApi\Api $ziskejApi,
        \KnihovnyCz\Ziskej\ZiskejMvs $ziskejMvs
    ) {
        $this->_authManager = $authManager;
        $this->_ilsDriver = $ilsDriver;
        $this->_ziskejApi = $ziskejApi;
        $this->_ziskejMvs = $ziskejMvs;

        $this->isZiskejActive = $ziskejMvs->isEnabled();
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
        return 'tab_title_ziskej';
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
            && $this->getRecordDriver()->tryMethod('getZiskejBoolean');
    }

    /**
     * Get ZiskejMvs class
     *
     * @return \KnihovnyCz\Ziskej\ZiskejMvs
     */
    public function getZiskejMvs(): \KnihovnyCz\Ziskej\ZiskejMvs
    {
        return $this->_ziskejMvs;
    }

    /**
     * Get libraries connected in Ziskej
     *
     * @return array[]
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \VuFind\Exception\LibraryCard
     */
    public function getConnectedLibs(): array
    {
        $connectedLibs = [];

        $user = $this->_authManager->isLoggedIn();
        if ($user) {
            /**
             * User library card
             *
             * @var \VuFind\Db\Row\UserCard $userCard
             */
            foreach ($user->getLibraryCards() as $userCard) {
                $homeLibrary = $userCard->home_library ?? null;
                if (!empty($homeLibrary) && !empty($userCard->eppn)) {
                    if (in_array($homeLibrary, $this->getZiskejLibsIds())) {
                        $connectedLibs[$homeLibrary]['userCard'] = $userCard;
                        $connectedLibs[$homeLibrary]['ziskejReader']
                            = $this->_ziskejApi->getReader($userCard->eppn);
                    }
                }
            }
        }

        return $connectedLibs;
    }

    /**
     * Get ids of active libraries in Ziskej
     *
     * @return string[][]
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     */
    public function getZiskejLibsIds(): array
    {
        $ziskejLibsIds = [];

        $ziskejLibs = $this->_ziskejApi->getLibrariesActive()->getAll();

        foreach ($ziskejLibs as $ziskejLib) {
            /* @phpstan-ignore-next-line */
            $id = $this->_ilsDriver->siglaToSource($ziskejLib->getSigla());
            if (!empty($id)) {
                $ziskejLibsIds[] = $id;
            }
        }
        return $ziskejLibsIds;
    }
}
