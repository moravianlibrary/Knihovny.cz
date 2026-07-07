<?php

namespace KnihovnyCzConsole\Command\Util;

use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Class Users
 *
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
#[AsCommand(
    name: 'util/expire_users',
)]
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
}
