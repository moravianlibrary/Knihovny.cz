<?php

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

declare(strict_types=1);

namespace KnihovnyCz\RecordTab;

use KnihovnyCz\Ziskej\Ziskej;
use Laminas\Cache\Storage\StorageInterface;
use Mzk\ZiskejApi\Api;
use VuFind\Auth\Manager;
use VuFind\Cache\CacheTrait;
use VuFind\ILS\Connection;
use VuFind\RecordTab\AbstractBase;

use function count;
use function in_array;
use function is_object;

/**
 * Class ZiskejBase
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordTab
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
abstract class ZiskejBase extends AbstractBase
{
    use CacheTrait;

    protected const CACHE_LIFETIME = 12 * 60 * 60;

    protected const CACHE_KEY = 'ziskejActiveLibraries';

    protected \Mzk\ZiskejApi\Api $ziskejApi;

    protected \VuFind\Auth\Manager $authManager;

    protected \VuFind\ILS\Connection $ilsDriver;

    protected \Laminas\Cache\Storage\StorageInterface $cacheStorage;

    protected bool $isZiskejActive = false;

    protected Ziskej $ziskej;

    /**
     * Constructor
     *
     * @param \VuFind\Auth\Manager                    $authManager  Authentication manager
     * @param \VuFind\ILS\Connection                  $ilsDriver    ILS driver
     * @param \Mzk\ZiskejApi\Api                      $ziskejApi    Ziskej API connector
     * @param \Laminas\Cache\Storage\StorageInterface $cacheStorage Cache storage
     * @param \KnihovnyCz\Ziskej\Ziskej               $ziskej       Ziskej ILL model
     */
    public function __construct(
        Manager $authManager,
        Connection $ilsDriver,
        Api $ziskejApi,
        StorageInterface $cacheStorage,
        Ziskej $ziskej
    ) {
        $this->authManager = $authManager;
        $this->ilsDriver = $ilsDriver;
        $this->ziskejApi = $ziskejApi;
        $this->cacheStorage = $cacheStorage;
        $this->ziskej = $ziskej;

        $this->isZiskejActive = $ziskej->isEnabled();

        $this->setCacheStorage($this->cacheStorage);
        $this->cacheLifetime = self::CACHE_LIFETIME;
    }

    /**
     * Get Ziskej type (MVS or EDD)
     *
     * @return string
     */
    abstract public function getType(): string;

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
     * Get connected libraries by type
     *
     * @return array
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \Psr\Http\Client\ClientExceptionInterface
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
                    if (in_array($homeLibrary, $this->getZiskejLibsIds($this->getType()))) {
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
     * Check if any library in Ziskej has a record
     *
     * @return bool
     *
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function isActiveLibraries(): bool
    {
        return $this->isZiskejActive()
            && $this->getRecordDriver()->tryMethod(
                match ($this->getType()) {
                    ZiskejMvs::TYPE => 'getZiskejBoolean',
                    ZiskejEdd::TYPE => 'getEddBoolean',
                    default => '',
                }
            )
            && count(
                array_intersect(
                    array_keys(
                        $this->getRecordDriver()->tryMethod('getDeduplicatedRecords', [], [])
                    ),
                    $this->getCachedZiskejActiveLibraries($this->getType())
                )
            );
    }

    /**
     * Convert libraries from Ziskej to library codes
     *
     * @param \Mzk\ZiskejApi\ResponseModel\Library[] $ziskejLibs Array of libraries from Ziskej
     *
     * @return array Array of library codes
     */
    protected function convertLibsFromZiskej(array $ziskejLibs): array
    {
        $ziskejLibsIds = [];
        foreach ($ziskejLibs as $ziskejLib) {
            /* @phpstan-ignore-next-line */
            $id = $this->ilsDriver->siglaToSource($ziskejLib->sigla);
            if (!empty($id)) {
                $ziskejLibsIds[] = $id;
            }
        }
        return $ziskejLibsIds;
    }

    /**
     * Get ids of active libraries in Ziskej by type (mvs or edd)
     *
     * @param string $type Ziskej type (mvs or edd)
     *
     * @return string[][]
     *
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function getZiskejLibsIds(string $type): array
    {
        return match ($type) {
            ZiskejMvs::TYPE => $this->convertLibsFromZiskej(
                $this->ziskejApi->getLibrariesMvsActive()->getAll()
            ),
            ZiskejEdd::TYPE => $this->convertLibsFromZiskej(
                $this->ziskejApi->getLibrariesEddActive()->getAll()
            ),
            default => [],
        };
    }

    /**
     * Get cached ids of active libraries in Ziskej by type (mvs or edd)
     *
     * @param string $type Ziskej type (mvs or edd)
     *
     * @return \string[][]
     *
     * @throws \Mzk\ZiskejApi\Exception\ApiResponseException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function getCachedZiskejActiveLibraries(string $type): array
    {
        if ($this->cache) {
            $key = self::CACHE_KEY . '_' . $type;
            if (!$this->getCachedData($key)) {
                $this->putCachedData($key, $this->getZiskejLibsIds($type));
            }
            return $this->getCachedData($key);
        } else {
            return $this->getZiskejLibsIds($type);
        }
    }
}
