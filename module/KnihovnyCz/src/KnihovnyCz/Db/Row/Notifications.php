<?php

namespace KnihovnyCz\Db\Row;

use DateTime;
use KnihovnyCz\Db\Entity\NotificationsEntityInterface;
use Laminas\Db\Adapter\Adapter;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Service\DbServiceAwareInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Service\UserServiceInterface;

/**
 * Class Notifications
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 *
 * @property int    $id
 * @property int    $visibility
 * @property int    $priority
 * @property int    $author_id
 * @property string $content
 * @property string $change_date
 * @property string $create_date
 * @property string $language
 */
class Notifications extends RowGateway implements NotificationsEntityInterface, DbServiceAwareInterface
{
    use DbServiceAwareTrait;

    protected const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * Constructor
     *
     * @param Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('id', 'notifications', $adapter);
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
     * @param int $id Id of entity
     *
     * @return NotificationsEntityInterface
     */
    public function setId(int $id): NotificationsEntityInterface
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Visibility getter
     *
     * @return int
     */
    public function getVisibility(): int
    {
        return $this->visibility;
    }

    /**
     * Visibility setter
     *
     * @param int $visibility Visibility
     *
     * @return NotificationsEntityInterface
     */
    public function setVisibility(int $visibility): NotificationsEntityInterface
    {
        $this->visibility = $visibility;
        return $this;
    }

    /**
     * Priority getter
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Priority setter
     *
     * @param int $priority Priority
     *
     * @return NotificationsEntityInterface
     */
    public function setPriority(int $priority): NotificationsEntityInterface
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Author id getter
     *
     * @return ?UserEntityInterface
     */
    public function getAuthor(): ?UserEntityInterface
    {
        return $this->author_id
            ? $this->getDbServiceManager()->get(UserServiceInterface::class)->getUserById($this->author_id)
            : null;
    }

    /**
     * Author id setter
     *
     * @param ?UserEntityInterface $author Creator
     *
     * @return NotificationsEntityInterface
     */
    public function setAuthor(?UserEntityInterface $author): NotificationsEntityInterface
    {
        $this->author_id = $author?->getId();
        return $this;
    }

    /**
     * Content getter
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Content setter
     *
     * @param string $content Content
     *
     * @return NotificationsEntityInterface
     */
    public function setContent(string $content): NotificationsEntityInterface
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Changed date getter
     *
     * @return DateTime
     */
    public function getChangeDate(): DateTime
    {
        return DateTime::createFromFormat(self::DATE_FORMAT, $this->change_date);
    }

    /**
     * Changed date setter
     *
     * @param DateTime $changeDate Date of modification
     *
     * @return NotificationsEntityInterface
     */
    public function setChangeDate(DateTime $changeDate): NotificationsEntityInterface
    {
        $this->change_date = $changeDate->format(self::DATE_FORMAT);
        return $this;
    }

    /**
     * Create date getter
     *
     * @return DateTime
     */
    public function getCreateDate(): DateTime
    {
        return DateTime::createFromFormat(self::DATE_FORMAT, $this->create_date);
    }

    /**
     * Create date setter
     *
     * @param DateTime $createDate Date of creation
     *
     * @return NotificationsEntityInterface
     */
    public function setCreateDate(DateTime $createDate): NotificationsEntityInterface
    {
        $this->create_date = $createDate->format(self::DATE_FORMAT);
        return $this;
    }

    /**
     * Language getter
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Language setter
     *
     * @param string $language UI language
     *
     * @return NotificationsEntityInterface
     */
    public function setLanguage(string $language): NotificationsEntityInterface
    {
        $this->language = $language;
        return $this;
    }
}
