<?php

declare(strict_types=1);

namespace KnihovnyCz\Markdown\EmbeddedUrlGenerator;

use KnihovnyCz\Markdown\EmbeddedUrlGenerator\Node\EmbeddedUrlGenerator;
use KnihovnyCz\Markdown\EmbeddedUrlGenerator\Node\EmbeddedUrlGeneratorPlaceholder;
use Laminas\View\Renderer\PhpRenderer;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\NodeIterator;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

/**
 * Class EmbeddedUrlGeneratorBuilder
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Markdown\EmbeddedUrlGenerator
 * @author   Pavel Patek <pavel.patek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class EmbeddedUrlGeneratorBuilder implements ConfigurationAwareInterface
{
    protected ConfigurationInterface $config;

    /**
     * Constructor
     *
     * @param PhpRenderer $renderer Laminas PhpRenderer
     */
    public function __construct(private readonly PhpRenderer $renderer)
    {
    }

    /**
     * Handler for onDocumentParsed event
     *
     * @param DocumentParsedEvent $event Event object
     *
     * @return void
     */
    public function onDocumentParsed(DocumentParsedEvent $event): void
    {
        $document = $event->getDocument();
        $this->replacePlaceholders($document, [$this, 'generate']);
    }

    /**
     * Generate node tree
     *
     * @return EmbeddedUrlGenerator
     */
    protected function generate(): EmbeddedUrlGenerator
    {
        $embeddedUrlGenerator = new EmbeddedUrlGenerator();

        $viewModel = new \Laminas\View\Model\ViewModel();
        $viewModel->setTemplate('additionalContent/embeddedUrlGenerator');
        $viewModel->setTerminal(true);

        $formHtmlBlock = new HtmlBlock(HtmlBlock::TYPE_1_CODE_CONTAINER);
        $formHtmlBlock->setLiteral($this->renderer->render($viewModel));

        $embeddedUrlGenerator->appendChild($formHtmlBlock);

        return $embeddedUrlGenerator;
    }

    /**
     * Replace placeholder with generated node tree
     *
     * @param Document $document                     Main document
     * @param callable $embeddedUrlGeneratorFunction Function which generates node tree
     *
     * @return void
     */
    protected function replacePlaceholders(
        Document $document,
        callable $embeddedUrlGeneratorFunction
    ): void {
        foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            // Add the block once we find a placeholder
            if (! $node instanceof EmbeddedUrlGeneratorPlaceholder) {
                continue;
            }
            $embeddedUrlGenerator = $embeddedUrlGeneratorFunction();
            if ($embeddedUrlGenerator !== null) {
                $node->replaceWith(clone $embeddedUrlGenerator);
            }
        }
    }

    /**
     * Sets extension configuration
     *
     * @param ConfigurationInterface $configuration Configuration
     *
     * @return void
     */
    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }
}
