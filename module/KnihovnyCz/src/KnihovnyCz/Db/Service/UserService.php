<?php

namespace KnihovnyCz\Db\Service;

use DateTime;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\Feature\DeleteExpiredInterface;
use VuFind\Db\Service\UserService as Base;

/**
 * Class UserService
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Row
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserService extends Base implements DeleteExpiredInterface
{
    /**
     * Retrieve a user object from the database based on the given field.
     * Field name must be id, username, email, verify_hash or cat_id.
     *
     * @param string          $fieldName  Field name
     * @param int|string|null $fieldValue Field value
     *
     * @return ?UserEntityInterface
     */
    public function getUserByField(string $fieldName, int|string|null $fieldValue): ?UserEntityInterface
    {
        if ($fieldName == 'edu_person_unique_id') {
            return $this->getDbTable('User')->getByEduPersonUniqueId($fieldValue);
        }
        return parent::getUserByField($fieldName, $fieldValue);
    }

    /**
     * Build a user entity using data from a session container. Return null if user
     * data cannot be found.
     *
     * @return ?UserEntityInterface
     */
    public function getUserFromSession(): ?UserEntityInterface
    {
        $user = parent::getUserFromSession();
        if ($user != null && isset($this->userSessionContainer->userDetails)) {
            $details = $this->userSessionContainer->userDetails;
            $user->setFirstname($details['firstname']);
            $user->setLastname($details['lastname']);
            $user->setEmail($details['email']);
        }
        return $user;
    }

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
        return $this->getDbTable('User')->deleteExpired($dateLimit->format('Y-m-d H:i:s'), $limit);
    }
}
