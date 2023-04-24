<?php

/**
 * Class InvolvedLibrariesRenderer
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
 * @package  KnihovnyCz\Markdown\InvolvedLibraries
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Markdown\InvolvedLibraries;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Xml\XmlNodeRendererInterface;

/**
 * Class InvolvedLibrariesRenderer
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Markdown\InvolvedLibraries
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InvolvedLibrariesRenderer implements
    NodeRendererInterface,
    XmlNodeRendererInterface
{
    /**
     * Main node renderer
     *
     * @var NodeRendererInterface&XmlNodeRendererInterface
     */
    protected $innerRenderer;

    /**
     * Constructor
     *
     * @param NodeRendererInterface $innerRenderer Main node renderer
     */
    public function __construct(NodeRendererInterface $innerRenderer)
    {
        $this->innerRenderer = $innerRenderer;
    }

    /**
     * Render node
     *
     * @param Node                       $node          Node to render
     * @param ChildNodeRendererInterface $childRenderer Child nodes renderer
     *
     * @return string
     */
    public function render(
        Node $node,
        ChildNodeRendererInterface $childRenderer
    ): string {
        return
            '<!-- involved libraries -->' .
            $this->innerRenderer->render($node, $childRenderer);
    }

    /**
     * Get tag for XML output
     *
     * @param Node $node Document node
     *
     * @return string
     */
    public function getXmlTagName(Node $node): string
    {
        return 'involved_libraries';
    }

    /**
     * Get attributes for XML output
     *
     * @param Node $node Document node
     *
     * @return array<string, scalar>
     */
    public function getXmlAttributes(Node $node): array
    {
        return $this->innerRenderer->getXmlAttributes($node);
    }
}
