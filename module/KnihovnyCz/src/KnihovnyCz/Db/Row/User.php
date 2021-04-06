<?php declare(strict_types=1);

namespace KnihovnyCz\Db\Row;

/**
 * Class User
 *
 * @package KnihovnyCz\Db\Row
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
class User extends \VuFind\Db\Row\User
{
    public function __construct($adapter)
    {
        parent::__construct($adapter);
    }
}
