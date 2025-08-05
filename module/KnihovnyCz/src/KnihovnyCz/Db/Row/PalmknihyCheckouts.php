<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Row;

use DateTime;
use KnihovnyCz\Db\Entity\PalmknihyCheckoutsEntityInterface;
use Laminas\Db\Adapter\Adapter;
use VuFind\Db\Entity\UserCardEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Service\DbServiceAwareInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Service\UserCardServiceInterface;
use VuFind\Db\Service\UserServiceInterface;
use VuFind\RecordDriver\AbstractBase as AbstractRecord;

/**
 * Class PalmknihyCheckouts
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 *
 * @property int    $id
 * @property int    $user_id
 * @property int    $user_card_id
 * @property string $email
 * @property string $record_id
 * @property string $source
 * @property string $library_id
 * @property string $title
 * @property string $author
 * @property string $year
 * @property string $timestamp
 * @property int    $status
 * @property string $status_text
 */
class PalmknihyCheckouts extends RowGateway implements PalmknihyCheckoutsEntityInterface, DbServiceAwareInterface
{
    use DbServiceAwareTrait;

    protected const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * Constructor
     *
     * @param Adapter $adapter Database adapter
     */
    public function __construct(Adapter $adapter)
    {
        parent::__construct('id', 'palmknihy_checkouts', $adapter);
    }

    /**
     * Id getter
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Id setter
     *
     * @param int $id Id
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setId(int $id): PalmknihyCheckoutsEntityInterface
    {
        $this->id = $id;
        return $this;
    }

    /**
     * User getter
     *
     * @return ?UserEntityInterface
     */
    public function getUser(): ?UserEntityInterface
    {
        return $this->user_id
            ? $this->getDbServiceManager()->get(UserServiceInterface::class)->getUserById($this->user_id)
            : null;
    }

    /**
     * User setter
     *
     * @param ?UserEntityInterface $user User entity object
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setUser(?UserEntityInterface $user): PalmknihyCheckoutsEntityInterface
    {
        $this->user_id = $user?->getId();
        return $this;
    }

    /**
     * User card getter
     *
     * @return ?UserCardEntityInterface
     */
    public function getUserCard(): ?UserCardEntityInterface
    {
        return $this->user_id && $this->user_card_id
            ? $this->getDbServiceManager()
                ->get(UserCardServiceInterface::class)
                ->getOrCreateLibraryCard($this->user_id, $this->user_card_id)
            : null;
    }

    /**
     * Card setter
     *
     * @param ?UserCardEntityInterface $card User card entity object
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setUserCard(?UserCardEntityInterface $card): PalmknihyCheckoutsEntityInterface
    {
        $this->user_card_id = $card?->getId();
        return $this;
    }

    /**
     * Email getter
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Email setter
     *
     * @param string $email User email
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setEmail(string $email): PalmknihyCheckoutsEntityInterface
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Record identifier getter
     *
     * @return string
     */
    public function getRecordId(): string
    {
        return $this->record_id;
    }

    /**
     * Get source identifier
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Record setter
     *
     * @param AbstractRecord $record Record to operate with
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setRecord(AbstractRecord $record): PalmknihyCheckoutsEntityInterface
    {
        $this->record_id = $record->getUniqueID();
        $this->source = $record->getSourceIdentifier();
        return $this;
    }

    /**
     * PalmknihyDocId getter
     *
     * @return string
     */
    public function getPalmknihyDocId(): string
    {
        [, $palmknihyDocId] = explode('.', $this->record_id, 2);
        return $palmknihyDocId;
    }

    /**
     * LibraryId getter
     *
     * @return string
     */
    public function getLibraryId(): string
    {
        return $this->library_id;
    }

    /**
     * LibraryId setter
     *
     * @param string $libraryId Library identifier
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setLibraryId(string $libraryId): PalmknihyCheckoutsEntityInterface
    {
        $this->library_id = $libraryId;
        return $this;
    }

    /**
     * Title getter
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Title setter
     *
     * @param string $title Title
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setTitle(string $title): PalmknihyCheckoutsEntityInterface
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Author getter
     *
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Author setter
     *
     * @param string $author Primary author
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setAuthor(string $author): PalmknihyCheckoutsEntityInterface
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Year getter
     *
     * @return string
     */
    public function getYear(): string
    {
        return $this->year;
    }

    /**
     * Year setter
     *
     * @param string $year Publication year
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setYear(string $year): PalmknihyCheckoutsEntityInterface
    {
        $this->year = $year;
        return $this;
    }

    /**
     * Timestamp getter
     *
     * @return DateTime
     */
    public function getTimestamp(): DateTime
    {
        return DateTime::createFromFormat(self::DATE_FORMAT, $this->timestamp);
    }

    /**
     * Timestamp setter
     *
     * @param DateTime $timestamp Timestamp
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setTimestamp(DateTime $timestamp): PalmknihyCheckoutsEntityInterface
    {
        $this->timestamp = $timestamp->format(self::DATE_FORMAT);
        return $this;
    }

    /**
     * Status getter
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Status setter
     *
     * @param int $status Status code
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setStatus(int $status): PalmknihyCheckoutsEntityInterface
    {
        $this->status = $status;
        return $this;
    }

    /**
     * StatusText getter
     *
     * @return string
     */
    public function getStatusText(): string
    {
        return $this->status_text;
    }

    /**
     * StatusText setter
     *
     * @param string $statusText Status text
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function setStatusText(string $statusText): PalmknihyCheckoutsEntityInterface
    {
        $this->status_text = $statusText;
        return $this;
    }
}
