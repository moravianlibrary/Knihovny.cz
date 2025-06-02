<?php

declare(strict_types=1);

namespace KnihovnyCz\Markdown\EmbeddedUrlGenerator;

use KnihovnyCz\Markdown\EmbeddedUrlGenerator\Node\EmbeddedUrlGeneratorPlaceholder;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

/**
 * Class EmbeddedUrlGeneratorParser
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Markdown\EmbeddedUrlGenerator
 * @author   Pavel Patek <pavel.patek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class EmbeddedUrlGeneratorParser extends AbstractBlockContinueParser
{
    /**
     * Block
     *
     * @var EmbeddedUrlGeneratorPlaceholder
     */
    protected EmbeddedUrlGeneratorPlaceholder $block;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->block = new EmbeddedUrlGeneratorPlaceholder();
    }

    /**
     * Get main node
     *
     * @return EmbeddedUrlGeneratorPlaceholder
     */
    public function getBlock(): EmbeddedUrlGeneratorPlaceholder
    {
        return $this->block;
    }

    /**
     * Attempt to parse the given line
     *
     * @param Cursor                       $cursor            Document cursor
     * @param BlockContinueParserInterface $activeBlockParser Current parser
     *
     * @return BlockContinue|null
     */
    public function tryContinue(
        Cursor $cursor,
        BlockContinueParserInterface $activeBlockParser
    ): ?BlockContinue {
        return BlockContinue::none();
    }

    /**
     * Create BlockStartParser
     *
     * @return BlockStartParserInterface
     */
    public static function blockStartParser(): BlockStartParserInterface
    {
        return new class () implements BlockStartParserInterface, ConfigurationAwareInterface {
            protected ConfigurationInterface $config;

            /**
             * Check whether we should handle the block at the current position
             *
             * @param Cursor                       $cursor      A cloned copy of the
             * cursor at the current parsing location
             * @param MarkdownParserStateInterface $parserState Additional
             * information about the state of the Markdown parser
             *
             * @return BlockStart|null The BlockStart that has been identified, or
             * null if the block doesn't match here
             */
            public function tryStart(
                Cursor $cursor,
                MarkdownParserStateInterface $parserState
            ): ?BlockStart {
                $placeholder = $this->config->get('embedded_url_generator/placeholder');

                // The placeholder must be the only thing on the line
                $regex = '/^' . preg_quote($placeholder, '/') . '$/';
                if ($cursor->match($regex) === null) {
                    return BlockStart::none();
                }

                return BlockStart::of(new EmbeddedUrlGeneratorParser())->at($cursor);
            }

            /**
             * Set extension configuration
             *
             * @param ConfigurationInterface $configuration Extension configuration
             *
             * @return void
             */
            public function setConfiguration(
                ConfigurationInterface $configuration
            ): void {
                $this->config = $configuration;
            }
        };
    }
}
