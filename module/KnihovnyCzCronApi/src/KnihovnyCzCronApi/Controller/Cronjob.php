<?php

declare(strict_types=1);

namespace KnihovnyCzCronApi\Controller;

use KnihovnyCzConsole\Command\Util\ExpireCsrfTokensCommand;
use KnihovnyCzConsole\Command\Util\ExpireUsersCommand;
use KnihovnyCzConsole\Command\Util\UpdateRecordStatus;
use Laminas\Http\Response as HttpResponse;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use VuFindConsole\Command\PluginManager as CommandPluginManager;
use VuFindConsole\Command\Util\ExpireSearchesCommand;
use VuFindConsole\Command\Util\SitemapCommand;

/**
 * Class Cronjob
 *
 * @category VuFind
 * @package  KnihovnyCzCronApi
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Cronjob extends \VuFind\Controller\AbstractBase
{
    /**
     * Test endpoint
     *
     * @return HttpResponse
     */
    public function testAction(): HttpResponse
    {
        $response = new HttpResponse();
        $response->setContent('OK');
        return $response;
    }

    /**
     * Expire users endpoint
     *
     * @return HttpResponse
     */
    public function expireUsersAction(): HttpResponse
    {
        $input = new ArrayInput(['age' => '730',]);
        return $this->runCommand(ExpireUsersCommand::class, $input);
    }

    /**
     * Expire csrf tokens endpoint
     *
     * @return HttpResponse
     */
    public function expireCsrfTokensAction(): HttpResponse
    {
        $input = new ArrayInput(['age' => 3]);
        return $this->runCommand(ExpireCsrfTokensCommand::class, $input);
    }

    /**
     * Expire searches endpoint
     *
     * @return HttpResponse
     */
    public function expireSearchesAction(): HttpResponse
    {
        $input = new ArrayInput(['age' => '7']);
        return $this->runCommand(ExpireSearchesCommand::class, $input);
    }

    /**
     * Site map endpoint
     *
     * @return HttpResponse
     */
    public function siteMapAction(): HttpResponse
    {
        $input = new ArrayInput([]);
        return $this->runCommand(SitemapCommand::class, $input);
    }

    /**
     * Update availability totals endpoint
     *
     * @return HttpResponse
     */
    public function updateAvailabilityTotalsAction(): HttpResponse
    {
        $input = new ArrayInput(['--config' => true, '--type' => 'totals']);
        return $this->runCommand(UpdateRecordStatus::class, $input);
    }

    /**
     * Update availability loans endpoint
     *
     * @return HttpResponse
     */
    public function updateAvailabilityLoansAction(): HttpResponse
    {
        $input = new ArrayInput(['--config' => true, '--type' => 'loans']);
        return $this->runCommand(UpdateRecordStatus::class, $input);
    }

    /**
     * Run console command
     *
     * @param string         $commandName Command name
     * @param InputInterface $input       Command input
     *
     * @return HttpResponse
     */
    protected function runCommand(
        string $commandName,
        InputInterface $input
    ): HttpResponse {
        $pluginManager = $this->serviceLocator->get(CommandPluginManager::class);
        $command = $pluginManager->get($commandName);
        $output = new BufferedOutput();
        $command->run($input, $output);
        $response = new HttpResponse();
        $response->setContent($output->fetch());
        return $response;
    }
}
