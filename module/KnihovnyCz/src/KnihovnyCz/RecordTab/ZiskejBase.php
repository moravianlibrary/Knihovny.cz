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
    protected \Mzk\ZiskejApi\Api $ziskejApi;

    protected \VuFind\Auth\Manager $authManager;

    protected \VuFind\ILS\Connection $ilsDriver;

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
                            = $this->ziskejApi->getReader($userCard->eppn);
                    }
                }
            }
        }

        return $connectedLibs;
    }

    /**
     * Convert libraries from Ziskej to library codes
     *
     * @param  array $ziskejLibs Array of libraries from Ziskej
     * 
     * @return array Array of library codes
     */
    protected function convertLibsFromZiskej(array $ziskejLibs): array
    {
        $ziskejLibsIds = [];
        foreach ($ziskejLibs as $ziskejLib) {
            /* @phpstan-ignore-next-line */
            $id = $this->ilsDriver->siglaToSource($ziskejLib->getSigla());
            if (!empty($id)) {
                $ziskejLibsIds[] = $id;
            }
        }
        return $ziskejLibsIds;
    }
}
