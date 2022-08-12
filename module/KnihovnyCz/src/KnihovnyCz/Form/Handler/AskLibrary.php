<?php
declare(strict_types=1);

/**
 * Class AskLibrary
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2022.
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\Form\Handler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Form\Handler;

use Laminas\Log\LoggerAwareInterface;
use VuFind\Form\Handler\HandlerInterface;
use VuFind\Log\LoggerAwareTrait;
use VuFindHttp\HttpServiceAwareInterface;
use VuFindHttp\HttpServiceAwareTrait;

/**
 * Class AskLibrary
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Form\Handler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AskLibrary implements HandlerInterface, HttpServiceAwareInterface,
    LoggerAwareInterface
{
    use HttpServiceAwareTrait;
    use LoggerAwareTrait;

    /**
     * API URL
     *
     * @var string
     */
    protected string $baseUrl = 'https://www.ptejteseknihovny.cz/new_question.xml';

    /**
     * Get data from submitted form and process them.
     *
     * @param \VuFind\Form\Form                     $form   Submitted form
     * @param \Laminas\Mvc\Controller\Plugin\Params $params Request params
     * @param ?\VuFind\Db\Row\User                  $user   Authenticated user
     *
     * @return bool
     */
    public function handle(
        \VuFind\Form\Form $form,
        \Laminas\Mvc\Controller\Plugin\Params $params,
        ?\VuFind\Db\Row\User $user = null
    ): bool {
        $fields = $form->mapRequestParamsToFieldValues($params->fromPost());
        $fields = array_column($fields, 'value', 'name');
        unset($fields['name']);

        $url = $this->baseUrl . '?' . http_build_query($fields);

        $client = $this->httpService->createClient($url);
        try {
            $response = $client->send();
            $success = $response->isSuccess();
        } catch (\Exception $e) {
            $this->logError(
                'Could not contact server of Ask your library service: '
                . $e->getMessage()
            );
            return false;
        }
        if (!$success) {
            $this->logError(
                'Could not contact server of Ask your library service.'
            );
        }

        return $success;
    }
}
