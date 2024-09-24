<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Service;

use DateTime;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Service\Feature\DeleteExpiredInterface;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;

/**
 * Class CsrfTokenService
 *
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class CsrfTokenService extends AbstractDbService implements DeleteExpiredInterface, DbTableAwareInterface
{
    use DbTableAwareTrait;

    /**
     * Delete expired records. Allows setting a limit so that rows can be deleted in small batches.
     *
     * @param DateTime $dateLimit Date threshold of an "expired" record.
     * @param ?int     $limit     Maximum number of rows to delete or null for no limit.
     *
     * @return int Number of rows deleted
     */
    public function deleteExpired(DateTime $dateLimit, ?int $limit = null): int
    {
        return $this->getDbTable('CsrfToken')->deleteExpired($dateLimit->format('Y-m-d H:i:s'), $limit);
    }
}
