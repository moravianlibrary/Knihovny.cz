<?php

/**
 * Class InvolvedLibrariesCountParser
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2023.
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
 * @package  KnihovnyCz\Markdown\InvolvedLibrariesCount
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Markdown\InvolvedLibrariesCount;

use KnihovnyCz\Content\InvolvedLibrariesService;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * Class InvolvedLibrariesParser
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Markdown\InvolvedLibrariesCount
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InvolvedLibrariesCountParser implements InlineParserInterface
{
    /**
     * Constructor
     *
     * @param InvolvedLibrariesService $service     Involved libraries service
     * @param string                   $placeholder Placeholder to exchange
     */
    public function __construct(protected InvolvedLibrariesService $service, protected string $placeholder)
    {
    }

    /**
     * Find placeholder
     *
     * @return InlineParserMatch
     */
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::string($this->placeholder);
    }

    /**
     * Parse text and add the count of libraries as text
     *
     * @param InlineParserContext $inlineContext Text to parse
     *
     * @return bool
     */
    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $cursor->advanceBy($inlineContext->getFullMatchLength());
        $count = $this->service->getInvolvedLibrariesCount();
        $inlineContext->getContainer()->appendChild(new Text(((string)$count)));
        return true;
    }
}
