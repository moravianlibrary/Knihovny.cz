<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Entity;

use DateTime;
use VuFind\Db\Entity\EntityInterface;
use VuFind\Db\Entity\UserEntityInterface;

/**
 * Entity model interface for notifications.
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 */
interface NotificationsEntityInterface extends EntityInterface
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
     * @param int $id Id of entity
     *
     * @return NotificationsEntityInterface
     */
    public function setId(int $id): NotificationsEntityInterface;

    /**
     * Visibility getter
     *
     * @return int
     */
    public function getVisibility(): int;

    /**
     * Visibility setter
     *
     * @param int $visibility Visibility
     *
     * @return NotificationsEntityInterface
     */
    public function setVisibility(int $visibility): NotificationsEntityInterface;

    /**
     * Priority getter
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Priority setter
     *
     * @param int $priority Priority
     *
     * @return NotificationsEntityInterface
     */
    public function setPriority(int $priority): NotificationsEntityInterface;

    /**
     * Author id getter
     *
     * @return ?UserEntityInterface
     */
    public function getAuthor(): ?UserEntityInterface;

    /**
     * Author id setter
     *
     * @param ?UserEntityInterface $author Creator
     *
     * @return NotificationsEntityInterface
     */
    public function setAuthor(?UserEntityInterface $author): NotificationsEntityInterface;

    /**
     * Content getter
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Content setter
     *
     * @param string $content Content
     *
     * @return NotificationsEntityInterface
     */
    public function setContent(string $content): NotificationsEntityInterface;

    /**
     * Changed date getter
     *
     * @return DateTime
     */
    public function getChangeDate(): DateTime;

    /**
     * Changed date setter
     *
     * @param DateTime $changeDate Date of modification
     *
     * @return NotificationsEntityInterface
     */
    public function setChangeDate(DateTime $changeDate): NotificationsEntityInterface;

    /**
     * Create date getter
     *
     * @return DateTime
     */
    public function getCreateDate(): DateTime;

    /**
     * Create date setter
     *
     * @param DateTime $createDate Date of creation
     *
     * @return NotificationsEntityInterface
     */
    public function setCreateDate(DateTime $createDate): NotificationsEntityInterface;

    /**
     * Language getter
     *
     * @return string
     */
    public function getLanguage(): string;

    /**
     * Language setter
     *
     * @param string $language UI language
     *
     * @return NotificationsEntityInterface
     */
    public function setLanguage(string $language): NotificationsEntityInterface;
}
