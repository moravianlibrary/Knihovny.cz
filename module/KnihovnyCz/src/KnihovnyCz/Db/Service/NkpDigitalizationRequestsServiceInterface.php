<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Service;

use KnihovnyCz\Db\Entity\NkpDigitalizationRequestsEntityInterface;

/**
 * Database service interface for nkp_digitalization_requests.
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 */
interface NkpDigitalizationRequestsServiceInterface extends \VuFind\Db\Service\DbServiceInterface
{
    /**
     * Create a nkp_digitalization_requests entity object.
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function createEntity(): NkpDigitalizationRequestsEntityInterface;

    /**
     * Get request data
     *
     * @param int $id Request id
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function getById(int $id): NkpDigitalizationRequestsEntityInterface;

    /**
     * Count all requests in current month
     *
     * @return int
     */
    public function countAllRequestsInCurrentMonth(): int;

    /**
     * Count requests for a cat_username in current month
     *
     * @param string $catUsername Catalog username
     *
     * @return int
     */
    public function countUserRequestsInCurrentMonth(string $catUsername): int;
}
