<?php

/**
 * Class Ziskej
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
 * Class Ziskej
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Ziskej extends \VuFind\RecordTab\AbstractBase
{
    private \VuFind\Auth\Manager $authManager;

    private \Vufind\ILS\Connection $ilsDriver;

    private \Mzk\ZiskejApi\Api $ziskejApi;

    private \KnihovnyCz\Ziskej\ZiskejMvs $ziskejMvs;

    private bool $isZiskejActive = false;

    /**
     * @param \VuFind\Auth\Manager         $authManager
     * @param \Vufind\ILS\Connection       $ilsDriver
     * @param \Mzk\ZiskejApi\Api           $ziskejApi
     * @param \KnihovnyCz\Ziskej\ZiskejMvs $ziskejMvs
     */
    public function __construct(
        \VuFind\Auth\Manager $authManager,
        \Vufind\ILS\Connection $ilsDriver,
        \Mzk\ZiskejApi\Api $ziskejApi,
        \KnihovnyCz\Ziskej\ZiskejMvs $ziskejMvs
    ) {
        $this->authManager = $authManager;
        $this->ilsDriver = $ilsDriver;
        $this->ziskejApi = $ziskejApi;
        $this->ziskejMvs = $ziskejMvs;

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
    public function isVisible(): bool
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
        return $this->ziskejMvs;
    }

    /**
     * Return if ziskej is active
     *
     * @return bool
     */
    public function isZiskejActive(): bool
    {
        return $this->isZiskejActive;
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

        $user = $this->authManager->isLoggedIn();
        if ($user) {
            /** @var \VuFind\Db\Row\UserCard $userCard */
            foreach ($user->getLibraryCards() as $userCard) {
                if (!empty($userCard->home_library) && !empty($userCard->eppn)) {
                    if (in_array($userCard->home_library, $this->getZiskejLibsIds())) {
                        $connectedLibs[$userCard->home_library]['userCard'] = $userCard;
                        $connectedLibs[$userCard->home_library]['ziskejReader'] = $this->ziskejApi->getReader($userCard->eppn);
                    }
                }
            }
        }

        return $connectedLibs;
    }

    /**
     * Get server name
     *
     * @return string|null
     */
    public function getServerName(): ?string
    {
        return $this->getRequest()->getServer()->SERVER_NAME;
    }

    /**
     * Get entity id
     *
     * @return string|null
     */
    public function getEntityId(): ?string
    {
        return $this->getRequest()->getServer('Shib-Identity-Provider') ?: '';
    }

    /**
     * Get deduplicated records
     *
     * @return string[][]
     */
    public function getDedupedRecords(): array
    {
        return $this->driver->tryMethod('getDeduplicatedRecords', [], []);
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

        $ziskejLibs = $this->ziskejApi->getLibrariesActive()->getAll();

        foreach ($ziskejLibs as $ziskejLib) {
            $id = $this->ilsDriver->siglaToSource($ziskejLib->getSigla());
            if (!empty($id)) {
                $ziskejLibsIds[] = $id;
            }
        }
        return $ziskejLibsIds;
    }
}
