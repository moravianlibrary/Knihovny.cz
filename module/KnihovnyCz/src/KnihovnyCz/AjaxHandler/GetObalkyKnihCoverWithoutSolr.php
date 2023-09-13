<?php

/**
 * Class GetObalkyKnihCoverWithoutSolr
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
 * @package  KnihovnyCz\AjaxHandler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\PhpRenderer;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\AjaxHandler\AjaxHandlerInterface;
use VuFind\Content\Covers\ObalkyKnih;
use VuFind\Session\Settings as SessionSettings;
use VuFindCode\ISBN;
use VuFindCode\ISMN;

use function in_array;

/**
 * Class GetObalkyKnihCoverWithoutSolr
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\AjaxHandler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GetObalkyKnihCoverWithoutSolr extends AbstractBase implements
    AjaxHandlerInterface
{
    /**
     * Obálky knih cover handler
     *
     * @var ObalkyKnih
     */
    protected ObalkyKnih $coverHandler;

    /**
     * PHP renderer
     *
     * @var ?PhpRenderer
     */
    protected ?PhpRenderer $renderer;

    /**
     * If true we will render a fallback html template in case no image could be
     * loaded
     *
     * @var bool
     */
    protected bool $useCoverFallbacksOnFail = false;

    /**
     * Constructor
     *
     * @param SessionSettings  $ss                      Session settings
     * @param ObalkyKnih       $coverHandler            Cover handler (plugin)
     * @param PhpRenderer|null $renderer                PHP renderer (only required
     * if $userCoverFallbacksOnFail is set to true)
     * @param bool             $useCoverFallbacksOnFail If true we will render a
     * fallback html template in case no image could be loaded
     */
    public function __construct(
        SessionSettings $ss,
        ObalkyKnih $coverHandler,
        ?PhpRenderer $renderer = null,
        bool $useCoverFallbacksOnFail = false
    ) {
        $this->sessionSettings = $ss;
        $this->coverHandler = $coverHandler;
        $this->renderer = $renderer;
        $this->useCoverFallbacksOnFail = $useCoverFallbacksOnFail;
    }

    /**
     * Handle request
     *
     * @param Params $params Request parameters
     *
     * @return array
     * @throws \Exception
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();

        $size = $params->fromQuery('size', 'small');
        if (!in_array($size, ['small', 'medium', 'large'])) {
            return $this->formatResponse(
                'Not valid size: ' . $size,
                self::STATUS_HTTP_BAD_REQUEST
            );
        }
        $recordId = $params->fromQuery('recordId', '');
        $format = $params->fromQuery('format', '');
        $ids = ['recordid' => $recordId];
        $idWithoutPrefix = substr($recordId, strpos($recordId, '.') + 1);
        if (
            substr($idWithoutPrefix, 0, 5) === 'uuid:'
            && empty($ids['uuid'] ?? null)
        ) {
            $ids['uuid'] = $idWithoutPrefix;
        }
        foreach (['isbn', 'issn', 'ismn', 'ean', 'cnb', 'uuid'] as $id) {
            if ($value = $params->fromQuery($id, null)) {
                $value = ($id === 'isbn') ? new ISBN($value) : $value;
                $value = ($id === 'ismn') ? new ISMN($value) : $value;
                $id = ($id === 'cnb') ? 'nbn' : $id;
                $id = ($id === 'ean') ? 'upc' : $id;
                $ids[$id] = $value;
            }
        }
        $metadata = $this->coverHandler->getMetadata(null, $size, $ids);

        return ($metadata || !$this->renderer || !$this->useCoverFallbacksOnFail)
            ? $this->formatResponse(array_merge($metadata, compact('size')))
            : $this->formatResponse(
                [
                    'html' => $this->renderer->render(
                        'record/coverReplacement',
                        ['format' => $format]
                    ),
                ]
            );
    }
}
