<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Service;

use KnihovnyCz\Db\Entity\NotificationsEntityInterface;
use Laminas\Db\ResultSet\ResultSetInterface;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;

/**
 * Database service for notifications.
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 */
class NotificationsService extends AbstractDbService implements DbTableAwareInterface, NotificationsServiceInterface
{
    use DbTableAwareTrait;

    /**
     * Create a notifications entity object.
     *
     * @return NotificationsEntityInterface
     */
    public function createEntity(): NotificationsEntityInterface
    {
        return $this->getDbTable('notifications')->createRow();
    }

    /**
     * Get current notifications to show
     *
     * @param string $language UI language
     *
     * @return ResultSetInterface
     */
    public function getActiveNotifications(string $language = 'cs'): ResultSetInterface
    {
        return $this->getDbTable('notifications')->getActiveNotifications($language);
    }

    /**
     * Get current notifications to show
     *
     * @return ResultSetInterface
     */
    public function getAllNotifications(): ResultSetInterface
    {
        return $this->getDbTable('notifications')->getAllNotifications();
    }

    /**
     * Get notification data
     *
     * @param int $id Notification id
     *
     * @return NotificationsEntityInterface
     */
    public function getById(int $id): NotificationsEntityInterface
    {
        return $this->getDbTable('notifications')->select(['id' => $id])->current();
    }

    /**
     * Delete notification by id
     *
     * @param int $id Notification id
     *
     * @return bool
     */
    public function deleteById(int $id): bool
    {
        return (bool)$this->getDbTable('notifications')->delete(['id' => $id]);
    }
}
