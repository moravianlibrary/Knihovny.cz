<?php declare(strict_types=1);


namespace KnihovnyCz\Db\Row;


use Laminas\Db\Adapter\Adapter;

/**
 * Class UserCard
 *
 * @package KnihovnyCz\Db\Row
 *
 * @property int     $id
 * @property int     $user_id
 * @property string  $card_name
 * @property string  $cat_username
 * @property ?string $cat_password
 * @property ?string $cat_pass_enc
 * @property string  $home_library
 * @property string  $created
 * @property string  $saved
 * @property ?string $eppn
 * @property ?string $major
 */
class UserCard extends \VuFind\Db\Row\UserCard
{
    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct(Adapter $adapter)
    {
        parent::__construct($adapter);
    }

    public function getEppnDomain(): ?string
    {
        $eppnDomain = substr((string)strrchr((string)$this->eppn, "@"), 1);
        return $eppnDomain ? $eppnDomain : null;
    }
}
