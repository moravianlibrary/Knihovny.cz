<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Row;

use VuFind\Db\Row\User as Base;

/**
 * Class User
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Row
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 *
 * @property int     $id
 * @property ?string $username
 * @property string  $password
 * @property ?string $pass_hash
 * @property string  $firstname
 * @property string  $lastname
 * @property string  $email
 * @property ?string $email_verified
 * @property int     $user_provided_email
 * @property ?string $cat_id
 * @property ?string $cat_username
 * @property ?string $cat_password
 * @property ?string $cat_pass_enc
 * @property string  $college
 * @property string  $major
 * @property string  $home_library
 * @property string  $created
 * @property string  $verify_hash
 * @property string  $last_login
 * @property ?string $auth_method
 * @property string  $pending_email
 * @property string  $last_language
 */
class User extends Base
{
    /**
     * Get UserCard by card name
     *
     * @param string|null $catUsername Catalog user name (login)
     *
     * @return \KnihovnyCz\Db\Row\UserCard|null
     * @throws \VuFind\Exception\LibraryCard
     */
    public function getCardByCatName(?string $catUsername): ?UserCard
    {
        if ($catUsername !== null && $catUsername !== '') {
            /**
             * User library card
             *
             * @var \KnihovnyCz\Db\Row\UserCard $userCard
             */
            foreach ($this->getUserCardService()->getLibraryCards($this) as $userCard) {
                if ($userCard->cat_username === $catUsername) {
                    return $userCard;
                }
            }
        }

        return null;
    }

    /**
     * Get library card using EduPersonPrincipalName
     *
     * @param string $eppn EduPersonPrincipalName
     *
     * @return \KnihovnyCz\Db\Row\UserCard|null
     * @throws \VuFind\Exception\LibraryCard
     */
    public function getCardByEppn(string $eppn): ?UserCard
    {
        /**
         * User library card
         *
         * @var \KnihovnyCz\Db\Row\UserCard $userCard
         */
        foreach ($this->getUserCardService()->getLibraryCards($this) as $userCard) {
            if ($userCard->eppn === $eppn) {
                return $userCard;
            }
        }
        return null;
    }

    /**
     * Get UserCard by eppn domain
     *
     * @param string $eppnDomain EduPersonPrincipalName scope
     *
     * @return \KnihovnyCz\Db\Row\UserCard|null
     * @throws \VuFind\Exception\LibraryCard
     */
    public function getCardByEppnDomain(string $eppnDomain): ?UserCard
    {
        /**
         * User library card
         *
         * @var \KnihovnyCz\Db\Row\UserCard $userCard
         */
        foreach ($this->getUserCardService()->getLibraryCards($this) as $userCard) {
            if ($userCard->getEppnDomain() === $eppnDomain) {
                return $userCard;
            }
        }

        return null;
    }

    /**
     * Delete library card
     *
     * @param int $id Library card ID
     *
     * @return void
     * @throws \VuFind\Exception\LibraryCard
     */
    public function deleteLibraryCard($id): void
    {
        if (!$this->capabilities->libraryCardsEnabled()) {
            throw new \VuFind\Exception\LibraryCard('Library Cards Disabled');
        }

        $cards = $this->getUserCardService()->getLibraryCards($this);
        if ($cards->count() <= 1) {
            throw new \Exception('Library card cannot be deleted');
        }

        $userCard = $this->getDbTable('UserCard');
        $row = $userCard->select(['id' => $id, 'user_id' => $this->id])->current();

        if (empty($row)) {
            throw new \Exception('Library card not found');
        }
        $row->delete();

        if ($row->edu_person_unique_id != $this->username) {
            return;
        }

        foreach ($cards as $card) {
            if ($card->id != $row->id) {
                $this->activateLibraryCard($card->id);
                break;
            }
        }
    }

    /**
     * Activate a library card for the given username
     *
     * @param int $id Library card ID
     *
     * @return void
     * @throws \VuFind\Exception\LibraryCard
     */
    public function activateLibraryCard($id): void
    {
        if (!$this->capabilities->libraryCardsEnabled()) {
            throw new \VuFind\Exception\LibraryCard('Library Cards Disabled');
        }
        $userCard = $this->getDbTable('UserCard');
        $row = $userCard->select(['id' => $id, 'user_id' => $this->id])->current();

        if (!empty($row)) {
            $this->username = $row->edu_person_unique_id;
            $this->cat_username = $row->cat_username;
            $this->cat_password = $row->cat_password;
            $this->cat_pass_enc = $row->cat_pass_enc;
            $this->home_library = $row->home_library;
            $this->save();
        }
    }

    /**
     * Save
     *
     * @return int
     */
    public function save(): int
    {
        // modification for GDPR - do not store last name, first name and email
        // in database, empty and restore them after saving
        $firstname = $this->firstname;
        $lastname = $this->lastname;
        $email = $this->email;
        $this->firstname = '';
        $this->lastname = '';
        $this->email = '';
        $id = parent::save();
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        return $id;
    }

    /**
     * Get library prefixes from connected library cards
     *
     * @return array
     */
    public function getLibraryPrefixes()
    {
        if (!$this->capabilities->libraryCardsEnabled()) {
            return [];
        }
        $myLibs = [];
        foreach ($this->getUserCardService()->getLibraryCards($this) as $libCard) {
            $ids = explode('.', $libCard['cat_username'] ?? '', 2);
            if (count($ids) == 2) {
                $myLibs[] = $ids[0];
            }
        }
        return array_unique($myLibs);
    }

    /**
     * Verify that the current card information exists in user's library cards
     * (if enabled) and is up to date.
     *
     * @return void
     * @throws \VuFind\Exception\PasswordSecurity
     */
    protected function updateLibraryCardEntry(): void
    {
        if (!$this->capabilities->libraryCardsEnabled() || empty($this->cat_username)) {
            return;
        }

        $userCard = $this->getDbTable('UserCard');
        $row = $userCard->select(
            ['user_id' => $this->id, 'cat_username' => $this->cat_username]
        )->current();
        if (empty($row)) {
            return;
        }
        // Always update home library and password
        $row->home_library = $this->home_library;
        $row->cat_password = $this->cat_password;
        $row->cat_pass_enc = $this->cat_pass_enc;
        $row->save();
    }

    /**
     * Activate the user card by library prefix
     *
     * @param string $source Library prefix
     *
     * @return bool
     * @throws \VuFind\Exception\LibraryCard
     */
    public function activateCardByPrefix(string $source): bool
    {
        foreach ($this->getUserCardService()->getLibraryCards($this) as $card) {
            if (($card['home_library'] ?? '') == $source) {
                $this->activateLibraryCard($card['id']);
                return true;
            }
        }
        return false;
    }

    /**
     * Get all library cards associated with the user with enabled ILS.
     *
     * @return array
     * @throws \VuFind\Exception\LibraryCard
     */
    public function getLibraryCardsWithILS(): array
    {
        $cards = [];
        $filter = [];
        if (isset($this->config->LibraryCards->filter)) {
            $filter = $this->config->LibraryCards->filter->toArray();
        }
        foreach ($this->getUserCardService()->getLibraryCards($this) as $card) {
            [$prefix, $username] = explode(
                '.',
                $card['cat_username'] ?? '',
                2
            );
            if (!empty($filter) && !in_array($prefix, $filter)) {
                continue;
            }
            if (!empty($username)) {
                $cards[] = $card;
            }
        }
        return $cards;
    }

    /**
     * Is enabled only single card?
     *
     * @return bool
     */
    public function isSingleCard(): bool
    {
        return (bool)($this->config->LibraryCards->singleCard ?? false);
    }

    /**
     * Is library card filter used?
     *
     * @return bool
     */
    public function hasLibraryCardsFilter(): bool
    {
        return isset($this->config->LibraryCards->filter);
    }

    /**
     * Return if the user is from social network - has no connected library
     * card with ILS.
     *
     * @return bool
     * @throws \VuFind\Exception\LibraryCard
     */
    public function isSocial(): bool
    {
        return empty($this->getLibraryCardsWithILS());
    }

    /**
     * Returns salted sha1 hashed id for purposes of google analytics
     *
     * @return string
     */
    public function getHashedId(): string
    {
        return hash(
            'sha1',
            $this->cat_username . ($this->config->GoogleTagManager->salt ?? '')
        );
    }

    /**
     * Returns string with connected institutions for purposes of Google Analytics
     * Institutions are separated by commas
     * For no institutions empty string is returned
     *
     * @return string
     */
    public function getConnectedInstitutionsForGTM(): string
    {
        return implode(',', $this->getLibraryPrefixes());
    }

    /**
     * Return user settings
     *
     * @return \KnihovnyCz\Db\Row\UserSettings
     */
    public function getUserSettings(): UserSettings
    {
        $userSettings = $this->getDbTable('UserSettings');
        return $userSettings->getOrCreateByUserId($this->id);
    }

    /**
     * Return true if user has privileges to create/edit inspiration lists
     *
     * @return bool
     */
    public function couldManageInspirationLists(): bool
    {
        return $this->hasPermission('widgets');
    }

    /**
     * Whether user could manage notifications
     *
     * @return bool
     */
    public function couldManageNotifications(): bool
    {
        return $this->hasPermission('notifications');
    }

    /**
     * Return true if user has permission
     *
     * @param string $permission Permission, could be 'admin', 'widgets' or 'any'
     *
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        if ($permission === 'any') {
            return !empty($this->major);
        }
        return str_contains($this->major, $permission);
    }

    /**
     * Destroy the user.
     *
     * @param bool $removeComments Whether to remove user's comments
     * @param bool $removeRatings  Whether to remove user's ratings
     *
     * @return int The number of rows deleted.
     */
    public function delete($removeComments = true, $removeRatings = true): int
    {
        if ($this->hasPermission('any')) {
            return 0;
        }
        return parent::delete($removeComments, $removeRatings);
    }
}
