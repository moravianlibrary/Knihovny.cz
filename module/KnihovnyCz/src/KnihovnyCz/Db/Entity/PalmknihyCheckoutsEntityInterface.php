<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Entity;

use DateTime;
use VuFind\Db\Entity\EntityInterface;
use VuFind\Db\Entity\UserCardEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\RecordDriver\AbstractBase as AbstractRecord;

/**
 * Entity model interface for palmknihy checkouts.
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 */
interface PalmknihyCheckoutsEntityInterface extends EntityInterface
{
    /**
     * Id getter
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Id setter
     *
     * @param int $id Id
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setId(int $id): PalmknihyCheckoutsEntityInterface;

    /**
     * User getter
     *
     * @return ?UserEntityInterface
     */
    public function getUser(): ?UserEntityInterface;

    /**
     * User setter
     *
     * @param ?UserEntityInterface $user User entity object
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setUser(?UserEntityInterface $user): PalmknihyCheckoutsEntityInterface;

    /**
     * User card getter
     *
     * @return ?UserCardEntityInterface
     */
    public function getUserCard(): ?UserCardEntityInterface;

    /**
     * Card setter
     *
     * @param ?UserCardEntityInterface $card User card entity object
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setUserCard(?UserCardEntityInterface $card): PalmknihyCheckoutsEntityInterface;

    /**
     * Email getter
     *
     * @return string
     */
    public function getEmail(): string;

    /**
     * Email setter
     *
     * @param string $email User email
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setEmail(string $email): PalmknihyCheckoutsEntityInterface;

    /**
     * Record identifier getter
     *
     * @return string
     */
    public function getRecordId(): string;

    /**
     * Get source identifier
     *
     * @return string
     */
    public function getSource(): string;

    /**
     * Record setter
     *
     * @param AbstractRecord $record Record to operate with
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setRecord(AbstractRecord $record): PalmknihyCheckoutsEntityInterface;

    /**
     * PalmknihyDocId getter
     *
     * @return string
     */
    public function getPalmknihyDocId(): string;

    /**
     * LibraryId getter
     *
     * @return string
     */
    public function getLibraryId(): string;

    /**
     * LibraryId setter
     *
     * @param string $libraryId Library identifier
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setLibraryId(string $libraryId): PalmknihyCheckoutsEntityInterface;

    /**
     * Title getter
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Title setter
     *
     * @param string $title Title
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setTitle(string $title): PalmknihyCheckoutsEntityInterface;

    /**
     * Author getter
     *
     * @return string
     */
    public function getAuthor(): string;

    /**
     * Author setter
     *
     * @param string $author Primary author
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setAuthor(string $author): PalmknihyCheckoutsEntityInterface;

    /**
     * Year getter
     *
     * @return string
     */
    public function getYear(): string;

    /**
     * Year setter
     *
     * @param string $year Publication year
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setYear(string $year): PalmknihyCheckoutsEntityInterface;

    /**
     * Timestamp getter
     *
     * @return DateTime
     */
    public function getTimestamp(): DateTime;

    /**
     * Timestamp setter
     *
     * @param DateTime $timestamp Timestamp
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setTimestamp(DateTime $timestamp): PalmknihyCheckoutsEntityInterface;

    /**
     * Status getter
     *
     * @return int
     */
    public function getStatus(): int;

    /**
     * Status setter
     *
     * @param int $status Status code
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setStatus(int $status): PalmknihyCheckoutsEntityInterface;

    /**
     * StatusText getter
     *
     * @return string
     */
    public function getStatusText(): string;

    /**
     * StatusText setter
     *
     * @param string $statusText Status text
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setStatusText(string $statusText): PalmknihyCheckoutsEntityInterface;
}
