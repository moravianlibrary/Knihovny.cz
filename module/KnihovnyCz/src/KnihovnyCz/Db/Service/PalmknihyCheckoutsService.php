<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Service;

use KnihovnyCz\Db\Entity\PalmknihyCheckoutsEntityInterface;
use KnihovnyCz\Db\Table\PalmknihyCheckouts;
use KnihovnyCz\Service\PalmknihyApiService;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Expression;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;
use VuFind\RecordDriver\AbstractBase as AbstractRecord;

/**
 * Database service for Palmknihy checkouts.
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 */
class PalmknihyCheckoutsService extends AbstractDbService implements
    DbTableAwareInterface,
    PalmknihyCheckoutsServiceInterface
{
    use DbTableAwareTrait;

    /**
     * Constructor.
     *
     * @param PalmknihyApiService $api     Palmknihy API service
     * @param PalmknihyCheckouts  $dbTable Palmknihy checkouts table
     */
    public function __construct(protected PalmknihyApiService $api, protected PalmknihyCheckouts $dbTable)
    {
    }

    /**
     * Create a Palmknihy checkout entity object.
     *
     * @return PalmknihyCheckoutsEntityInterface
     * @throws \Exception
     */
    public function createEntity(): PalmknihyCheckoutsEntityInterface
    {
        /**
         * Palmknihy checkouts entity object.
         *
         * @var PalmknihyCheckoutsEntityInterface
         */
        return $this->dbTable->createRow();
    }

    /**
     * Get the number of checkouts for a given user email.
     * Only checkouts that are active and not older than the configured lending interval are counted.
     *
     * @param string $email    User email
     * @param string $sourceId Source identifier
     *
     * @return int
     */
    public function getCheckoutsCountByUserEmail(string $email, string $sourceId): int
    {
        return $this->dbTable->select($this->getCallbackFunction($email, $sourceId))->count();
    }

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
    public function hasSameCheckout(string $email, AbstractRecord $record, string $sourceId): bool
    {
        return $this->dbTable->select(
            $this->getCallbackFunction($email, $sourceId, $record->getUniqueID())
        )->count() > 0;
    }

    /**
     * Get the current checkouts for a given user.
     * Only active checkouts and not older than the configured lending interval are counted.
     *
     * @param string $email    User email address
     * @param string $sourceId Source identifier
     *
     * @return ResultSetInterface
     */
    public function getCheckoutsForUser(string $email, string $sourceId): ResultSetInterface
    {
        return $this->dbTable->select($this->getCallbackFunction($email, $sourceId));
    }

    /**
     * Get the past checkouts for a given user.
     *
     * @param string $email    User email address
     * @param string $sourceId Source identifier
     *
     * @return ResultSetInterface
     */
    public function getCheckoutsHistoryForUser(string $email, string $sourceId): ResultSetInterface
    {
        return $this->dbTable->select($this->getCallbackFunction($email, $sourceId, null, true));
    }

    /**
     * Get a callback function for selecting data based on parameters.
     *
     * @param string      $email    User email
     * @param string      $source   Source identifier
     * @param string|null $recordId Record ID (optional)
     * @param bool        $history  Whether to get history records
     * @param int         $status   Status of the checkout (default is 1)
     *
     * @return \Closure
     */
    protected function getCallbackFunction(
        string $email,
        string $source,
        ?string $recordId = null,
        bool $history = false,
        int $status = 1
    ): \Closure {
        return function ($select) use ($email, $source, $recordId, $history, $status) {
            $where = $select->where->equalTo('email', $email)
                ->equalTo('library_id', $source)
                ->equalTo('status', $status);
            if ($recordId !== null) {
                $where->equalTo('record_id', $recordId);
            }
            $dateExpr = new Expression('NOW() - INTERVAL ? DAY', $this->api->getPalmknihyLendingInterval($source));
            if ($history) {
                $where->lessThan('timestamp', $dateExpr);
            } else {
                $where->greaterThanOrEqualTo('timestamp', $dateExpr);
            }
        };
    }
}
