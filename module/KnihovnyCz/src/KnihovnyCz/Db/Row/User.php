<?php
namespace KnihovnyCz\Db\Row;

use \VuFind\Db\Row\User as Base;

class User extends Base
{

    /**
     * Verify that the current card information exists in user's library cards
     * (if enabled) and is up to date.
     *
     * @return void
     * @throws \VuFind\Exception\PasswordSecurity
     */
    protected function updateLibraryCardEntry()
    {
        if (!$this->libraryCardsEnabled() || empty($this->cat_username)) {
            return;
        }

        $userCard = $this->getDbTable('UserCard');
        $row = $userCard->select(
            ['user_id' => $this->id, 'cat_username' => $this->cat_username]
        )->current();
        if (empty($row)) {
            $row = $userCard->createRow();
            $row->user_id = $this->id;
            $row->cat_username = $this->cat_username;
            $row->card_name = $this->cat_username;
            $row->edu_person_unique_id = $this->edu_person_unique_id;
            $row->created = date('Y-m-d H:i:s');
        }
        // Always update home library and password
        $row->home_library = $this->home_library;
        $row->cat_password = $this->cat_password;
        $row->cat_pass_enc = $this->cat_pass_enc;

        $row->save();
    }

}