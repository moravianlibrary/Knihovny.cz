<?php

namespace KnihovnyCzConsole\Command\Util;

use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Class ExpireCsrfTokensCommand
 *
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
#[AsCommand(
    name: 'util/expire_csrf_tokens',
)]
class ExpireCsrfTokensCommand extends \VuFindConsole\Command\Util\AbstractExpireCommand
{
    /**
     * Help description for the command.
     *
     * @var string
     */
    protected $commandDescription = 'Expired CSRF tokens cleanup';

    /**
     * Label to use for rows in help messages.
     *
     * @var string
     */
    protected $rowLabel = 'csrf tokens';
}
