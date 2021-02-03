<?php
declare(strict_types=1);

/**
 * Class Cronjob
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  KnihovnyCzCronApi
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCzCronApi\Controller;

use KnihovnyCzConsole\Command\Util\ExpireUsersCommand;
use KnihovnyCzConsole\Command\Util\HarvestEbooksCommand;
use Laminas\Http\Response as HttpResponse;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use VuFindConsole\Command\PluginManager as CommandPluginManager;

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
    public function testAction(): HttpResponse
    {
        $response = new HttpResponse();
        $response->setContent('OK');
        return $response;
    }

    public function expireUsersAction(): HttpResponse
    {
        $input = new ArrayInput(['age' => '730',]);
        return $this->runCommand(ExpireUsersCommand::class, $input);
    }

    public function harvestEbooksAction(): HttpResponse
    {
        $input = new ArrayInput([]);
        return $this->runCommand(HarvestEbooksCommand::class, $input);
    }

    protected function runCommand(string $commandName, InputInterface $input): HttpResponse
    {
        $pluginManager = $this->serviceLocator->get(CommandPluginManager::class);
        $command = $pluginManager->get($commandName);
        $output = new BufferedOutput();
        $command->run($input, $output);
        $response = new HttpResponse();
        $response->setContent($output->fetch());
        return $response;
    }
}
