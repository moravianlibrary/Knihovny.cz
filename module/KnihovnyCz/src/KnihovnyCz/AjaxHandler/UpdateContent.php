<?php

/**
 * Class UpdateContent
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
 * @package  KnihovnyCz\AjaxHandler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\AjaxHandler;

use GitWrapper\GitWorkingCopy;
use Laminas\Mvc\Controller\Plugin\Params;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use VuFind\Http\PhpEnvironment\Request;

/**
 * Class UpdateContent
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UpdateContent extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * Git service
     *
     * @var GitWorkingCopy
     */
    protected $git;

    /**
     * Git branch
     *
     * @var string
     */
    protected $branch;

    /**
     * Filesystem utils
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * UpdateContent constructor.
     *
     * @param GitWorkingCopy $git        Git service
     * @param string         $branch     Git branch
     * @param Filesystem     $filesystem File system util service
     */
    public function __construct(
        GitWorkingCopy $git,
        string $branch,
        Filesystem $filesystem
    ) {
        $this->git = $git;
        $this->branch = $branch;
        $this->filesystem = $filesystem;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $header = $params->fromHeader('X-Gitlab-Event');
        if ($header === null || $header->getFieldValue() !== 'Push Hook') {
            return $this->formatErrorResponse(
                'Bad request. This handler could handle only \'Push Hook\' type.'
            );
        }
        $controller = $params->getController();
        if ($controller === null) {
            throw new \Exception('Could not find controller');
        }
        $body = json_decode(
            $controller->getRequest()->getContent(),
            true
        );
        $ref = $body['ref'] ?? null;
        if (!preg_match('#refs/heads/(.*)#', $ref, $matches)) {
            return $this->formatErrorResponse(
                'Branch ref not found in request body'
            );
        }
        $branch = $matches[1];
        if ($branch !== $this->branch) {
            return $this->formatErrorResponse(
                "Branch '$branch' is not allowed to be updated on this instance"
            );
        }

        // All initial test are OK, we could try to do the content update
        $this->git->checkout($branch);
        $gitOutput = $this->git->pull();
        $sourceDir = $this->git->getDirectory() . '/data';
        $destinationDir = '/var/www/knihovny-cz-extension/themes/KnihovnyCz';

        try {
            $this->filesystem->mirror($sourceDir, $destinationDir);
        } catch (IOException $exception) {
            $this->formatErrorResponse(
                'Could not copy content to final destination: '
                . $exception->getMessage(),
                500
            );
        }
        $response = [
            'status' => 'OK',
            'branch' => $branch,
            'git_output' => $gitOutput,
        ];

        return $this->formatResponse($response, 200);
    }

    /**
     * Format error response
     *
     * @param string $message  Error message
     * @param int    $httpCode HTTP code
     *
     * @return array
     */
    protected function formatErrorResponse(string $message, int $httpCode = 400)
    {
        $response = [
            'status' => 'ERROR',
            'error' => $message,
        ];
        return $this->formatResponse($response, $httpCode);
    }
}
