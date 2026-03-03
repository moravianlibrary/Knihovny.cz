<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Service;

use KnihovnyCz\Db\Entity\NkpDigitalizationRequestsEntityInterface;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;

/**
 * Database service for nkp_digitalization_requests.
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 */
class NkpDigitalizationRequestsService extends AbstractDbService implements
    DbTableAwareInterface,
    NkpDigitalizationRequestsServiceInterface
{
    use DbTableAwareTrait;

    /**
     * Create a nkp_digitalization_requests entity object.
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function createEntity(): NkpDigitalizationRequestsEntityInterface
    {
        return $this->getDbTable('nkpDigitalizationRequests')->createRow();
    }

    /**
     * Get request data
     *
     * @param int $id Request id
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function getById(int $id): NkpDigitalizationRequestsEntityInterface
    {
        return $this->getDbTable('nkpDigitalizationRequests')->select(['id' => $id])->current();
    }

    /**
     * Count all requests in current month
     *
     * @return int
     */
    public function countAllRequestsInCurrentMonth(): int
    {
        $callback = function ($select) {
            $select->where->between('created', date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59'));
        };
        return $this->getDbTable('nkpDigitalizationRequests')->select($callback)->count();
    }

    /**
     * Count requests for a cat_username in current month
     *
     * @param string $catUsername Catalog username
     *
     * @return int
     */
    public function countUserRequestsInCurrentMonth(string $catUsername): int
    {
        $callback = function ($select) use ($catUsername) {
            $select->where->equalTo('cat_username', $catUsername);
            $select->where->between('created', date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59'));
        };
        return $this->getDbTable('nkpDigitalizationRequests')->select($callback)->count();
    }
}
