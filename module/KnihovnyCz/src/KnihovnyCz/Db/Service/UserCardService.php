<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Service;

use VuFind\Db\Entity\UserCardEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\UserServiceInterface;

/**
 * Class UserCardService
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserCardService extends \VuFind\Db\Service\UserCardService
{
    /**
     * Delete library card.
     *
     * @param UserEntityInterface         $user     User owning card to delete
     * @param UserCardEntityInterface|int $userCard UserCard id or object to be deleted
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteLibraryCard(UserEntityInterface $user, UserCardEntityInterface|int $userCard): bool
    {
        if (!$this->capabilities->libraryCardsEnabled()) {
            throw new \VuFind\Exception\LibraryCard('Library Cards Disabled');
        }
        $cards = $this->getLibraryCards($user);
        if (count($cards) <= 1) {
            throw new \VuFind\Exception\LibraryCard('Library card cannot be deleted');
        }
        $cardId = is_int($userCard) ? $userCard : $userCard->getId();
        $row = current($this->getLibraryCards($user, $cardId));
        if (!$row) {
            throw new \Exception('Library card not found');
        }
        $row->delete();

        if ($row->getEduPersonUniqueId() != $user->getUsername()) {
            return true;
        }
        $cards = $this->getLibraryCards($user);
        $this->activateLibraryCard($user, current($cards)->getId());

        return true;
    }

    /**
     * Activate a library card for the given username.
     *
     * @param UserEntityInterface|int $userOrId User owning card
     * @param int                     $id       Library card ID to activate
     *
     * @return void
     * @throws \VuFind\Exception\LibraryCard
     */
    public function activateLibraryCard(UserEntityInterface|int $userOrId, int $id): void
    {
        if (!$this->capabilities->libraryCardsEnabled()) {
            throw new \VuFind\Exception\LibraryCard('Library Cards Disabled');
        }
        $row = $this->getOrCreateLibraryCard($userOrId, $id);
        $user = is_int($userOrId)
            ? $this->getDbService(UserServiceInterface::class)->getUserById($userOrId) : $userOrId;

        $user->setUsername($row->getEduPersonUniqueId());
        $user->setCatUsername($row->getCatUsername());
        $user->setRawCatPassword($row->getRawCatPassword());
        $user->setCatPassEnc($row->getCatPassEnc());
        $user->setHomeLibrary($row->getHomeLibrary());
        $this->persistEntity($user);
    }
}
