<?php

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
