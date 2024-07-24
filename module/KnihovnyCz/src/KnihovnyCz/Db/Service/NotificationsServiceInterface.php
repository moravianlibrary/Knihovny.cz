<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Service;

use KnihovnyCz\Db\Entity\NotificationsEntityInterface;
use Laminas\Db\ResultSet\ResultSetInterface;

/**
 * Database service interface for notifications.
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 */
interface NotificationsServiceInterface extends \VuFind\Db\Service\DbServiceInterface
{
    /**
     * Create a notifications entity object.
     *
     * @return NotificationsEntityInterface
     */
    public function createEntity(): NotificationsEntityInterface;

    /**
     * Get current notifications to show
     *
     * @param string $language UI language
     *
     * @return ResultSetInterface
     */
    public function getActiveNotifications(string $language = 'cs'): ResultSetInterface;

    /**
     * Get current notifications to show
     *
     * @return ResultSetInterface
     */
    public function getAllNotifications(): ResultSetInterface;

    /**
     * Get notification data
     *
     * @param int $id Notification id
     *
     * @return NotificationsEntityInterface
     */
    public function getById(int $id): NotificationsEntityInterface;

    /**
     * Delete notification by id
     *
     * @param int $id Notification id
     *
     * @return bool
     */
    public function deleteById(int $id): bool;
}
