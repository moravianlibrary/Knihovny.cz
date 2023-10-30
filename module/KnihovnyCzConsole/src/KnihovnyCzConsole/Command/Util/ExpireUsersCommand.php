<?php

namespace KnihovnyCzConsole\Command\Util;

/**
 * Class Users
 *
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ExpireUsersCommand extends \VuFindConsole\Command\Util\AbstractExpireCommand
{
    /**
     * Help description for the command.
     *
     * @var string
     */
    protected $commandDescription = 'Expired users cleanup';

    /**
     * Label to use for rows in help messages.
     *
     * @var string
     */
    protected $rowLabel = 'users';

    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'util/expire_users';
}
