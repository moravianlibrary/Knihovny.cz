<?php

/**
 * Class InvolvedLibrariesParser
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

use KnihovnyCz\Markdown\InvolvedLibraries\Node\InvolvedLibrariesPlaceholder;
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
 * Class InvolvedLibrariesParser
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Markdown\InvolvedLibraries
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InvolvedLibrariesParser extends AbstractBlockContinueParser
{
    /**
     * Block
     *
     * @var InvolvedLibrariesPlaceholder
     */
    protected InvolvedLibrariesPlaceholder $block;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->block = new InvolvedLibrariesPlaceholder();
    }

    /**
     * Get main node
     *
     * @return InvolvedLibrariesPlaceholder
     */
    public function getBlock(): InvolvedLibrariesPlaceholder
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
                $placeholder = $this->config->get('involved_libraries/placeholder');

                // The placeholder must be the only thing on the line
                $regex = '/^' . preg_quote($placeholder, '/') . '$/';
                if ($cursor->match($regex) === null) {
                    return BlockStart::none();
                }

                return BlockStart::of(new InvolvedLibrariesParser())->at($cursor);
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
