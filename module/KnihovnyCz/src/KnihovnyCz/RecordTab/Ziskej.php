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
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\RecordTab;

/**
 * Class Ziskej
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Ziskej extends \VuFind\RecordTab\AbstractBase
{
    private \VuFind\Auth\Manager $authManager;

    private \Vufind\Ils\Connection $ilsDriver;

    private \Mzk\ZiskejApi\Api $ziskejApi;

    private \KnihovnyCz\Ziskej\ZiskejMvs $ziskejMvs;

    private bool $isZiskejActive = false;

    /**
     * @param \VuFind\Auth\Manager         $authManager
     * @param \Vufind\Ils\Connection       $ilsDriver
     * @param \Mzk\ZiskejApi\Api           $ziskejApi
     * @param \KnihovnyCz\Ziskej\ZiskejMvs $ziskejMvs
     */
    public function __construct(
        \VuFind\Auth\Manager $authManager,
        \Vufind\Ils\Connection $ilsDriver,
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
        return $this->getRecordDriver()->translate('tab_title_ziskej');
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
     * @return \KnihovnyCz\Db\Row\User|null
     */
    public function getUser(): ?\KnihovnyCz\Db\Row\User
    {
        return $this->authManager->isLoggedIn() ?: null;
    }

    /**
     * @return \KnihovnyCz\Ziskej\ZiskejMvs
     */
    public function getZiskejMvs(): \KnihovnyCz\Ziskej\ZiskejMvs
    {
        return $this->ziskejMvs;
    }

    /**
     * @return bool
     */
    public function isZiskejActive(): bool
    {
        return $this->isZiskejActive;
    }

    /**
     * @return string[][]
     *
     * @throws \Http\Client\Exception
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \VuFind\Exception\LibraryCard
     */
    public function getConnectedLibs(): array
    {
        $connectedLibs = [];

        $user = $this->getUser();

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
     * @return string|null
     */
    public function getServerName(): ?string
    {
        return $this->getRequest()->getServer()->SERVER_NAME;
    }

    /**
     * @return string|null
     */
    public function getEntityId(): ?string
    {
        return $this->getRequest()->getServer('Shib-Identity-Provider') ?: '';
    }

    /**
     * @return string[][]
     */
    public function getDeduperRecords(): array
    {
        return $this->driver->tryMethod('getDeduplicatedRecords', [], []);
    }

    /**
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
