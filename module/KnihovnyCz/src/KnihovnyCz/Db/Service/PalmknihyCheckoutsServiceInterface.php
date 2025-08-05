<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Service;

use KnihovnyCz\Db\Entity\PalmknihyCheckoutsEntityInterface;
use Laminas\Db\ResultSet\ResultSetInterface;
use VuFind\RecordDriver\AbstractBase as AbstractRecord;

/**
 * Database service interface for Palmknihy checkouts.
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 */
interface PalmknihyCheckoutsServiceInterface extends \VuFind\Db\Service\DbServiceInterface
{
    /**
     * Create a Palmknihy checkouts entity object.
     *
     * @return PalmknihyCheckoutsEntityInterface
     */
    public function createEntity(): PalmknihyCheckoutsEntityInterface;

    /**
     * Get the number of checkouts for a given user email.
     * Only checkouts that are active and not older than the configured lending interval are counted.
     *
     * @param string $email    User email
     * @param string $sourceId Source identifier
     *
     * @return int
     */
    public function getCheckoutsCountByUserEmail(string $email, string $sourceId): int;

    /**
     * Check if a user has already checked out the same book.
     * Only checkouts that are active and not older than the configured lending interval are considered.
     *
     * @param string         $email    User email
     * @param AbstractRecord $record   Record to check
     * @param string         $sourceId Source identifier
     *
     * @return bool
     */
    public function hasSameCheckout(string $email, AbstractRecord $record, string $sourceId): bool;

    /**
     * Get the current checkouts for a given user.
     * Only active checkouts and not older than the configured lending interval are counted.
     *
     * @param string $email    User email address
     * @param string $sourceId Source identifier
     *
     * @return ResultSetInterface
     */
    public function getCheckoutsForUser(string $email, string $sourceId): ResultSetInterface;

    /**
     * Get the past checkouts for a given user.
     *
     * @param string $email    User email address
     * @param string $sourceId Source identifier
     *
     * @return ResultSetInterface
     */
    public function getCheckoutsHistoryForUser(string $email, string $sourceId): ResultSetInterface;
}
