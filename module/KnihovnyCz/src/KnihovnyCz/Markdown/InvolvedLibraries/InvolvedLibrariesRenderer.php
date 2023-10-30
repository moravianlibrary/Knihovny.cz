<?php

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
