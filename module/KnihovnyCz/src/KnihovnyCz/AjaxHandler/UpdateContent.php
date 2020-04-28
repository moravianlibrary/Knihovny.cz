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

class UpdateContent extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * @var GitWorkingCopy
     */
    protected $git;

    /**
     * @var string
     */
    protected $branch;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * UpdateContent constructor.
     *
     * @param GitWorkingCopy $git
     */
    public function __construct(GitWorkingCopy $git, string $branch, Filesystem $filesystem)
    {
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
        /** @var Request $request */
        $request = $params->getController()->getRequest();
        $header = $request->getHeaders()->get('X-Gitlab-Event');
        if ($header === false || $header->getFieldValue() !== 'Push Hook') {
            return $this->formatErrorResponse(
                'Bad webhook type. This handler could handle only \'Push Hook\' type.'
            );
        }
        $body = json_decode($request->getContent(), true);
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
                . $exception->getMessage(), 500
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
     * @param string $message
     * @param int    $httpCode
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