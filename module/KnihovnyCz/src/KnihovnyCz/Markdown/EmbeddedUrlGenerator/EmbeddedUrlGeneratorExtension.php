<?php

declare(strict_types=1);

namespace KnihovnyCz\Markdown\EmbeddedUrlGenerator;

use KnihovnyCz\Markdown\EmbeddedUrlGenerator\Node\EmbeddedUrlGenerator;
use Laminas\View\Renderer\PhpRenderer;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\CommonMark\Renderer\Block\ParagraphRenderer;
use League\Config\ConfigurationBuilderInterface;
use Nette\Schema\Expect;

/**
 * Class EmbeddedUrlGeneratorExtension
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Markdown
 * @author   Pavel Patek <pavel.patek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class EmbeddedUrlGeneratorExtension implements ConfigurableExtensionInterface
{
    /**
     * Constructor
     *
     * @param PhpRenderer $renderer Laminas PhpRenderer for phtml template
     */
    public function __construct(private readonly PhpRenderer $renderer)
    {
    }

    /**
     * Configure schema
     *
     * @param ConfigurationBuilderInterface $builder Configuration builder
     *
     * @return void
     */
    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        $builder->addSchema(
            'embedded_url_generator',
            Expect::structure(
                [
                'placeholder' => Expect::string()->default('[EmbeddedUrlGenerator]'),
                ]
            )
        );
    }

    /**
     * Register extension
     *
     * @param EnvironmentBuilderInterface $environment Commonmark environment
     *
     * @return void
     */
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(
            EmbeddedUrlGenerator::class,
            new EmbeddedUrlGeneratorRenderer(new ParagraphRenderer())
        );
        $environment->addEventListener(
            DocumentParsedEvent::class,
            [
                new EmbeddedUrlGeneratorBuilder($this->renderer),
                'onDocumentParsed',
            ],
            -150
        );
        $environment->addBlockStartParser(
            EmbeddedUrlGeneratorParser::blockStartParser(),
            200
        );
    }
}
