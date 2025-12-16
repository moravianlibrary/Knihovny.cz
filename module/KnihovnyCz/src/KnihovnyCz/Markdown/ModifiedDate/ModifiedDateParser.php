<?php

declare(strict_types=1);

namespace KnihovnyCz\Markdown\ModifiedDate;

use KnihovnyCz\Content\GitLabService;
use KnihovnyCz\Content\PageLocator;
use Laminas\View\Renderer\PhpRenderer;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * Class ModifiedDateParser
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Markdown\ModifiedDate
 * @author   Pavel Pátek <pavel.patek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ModifiedDateParser implements InlineParserInterface
{
    /**
     * Constructor
     *
     * @param GitLabService $service     GitLab service
     * @param PageLocator   $pageLocator Page locator
     * @param PhpRenderer   $renderer    Php renderer
     * @param string        $placeholder Placeholder to exchange
     * @param string        $pageName    Page name
     * @param string        $dateFormat  Date format
     */
    public function __construct(
        protected GitLabService $service,
        protected PageLocator $pageLocator,
        protected PhpRenderer $renderer,
        protected string $placeholder,
        protected string $pageName,
        protected string $dateFormat
    ) {
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
     * Parse text and add modified   date
     *
     * @param InlineParserContext $inlineContext Text to parse
     *
     * @return bool
     */
    public function parse(InlineParserContext $inlineContext): bool
    {
        $modifiedDateBlock = $this->renderModifiedDate($this->pageName);

        $cursor = $inlineContext->getCursor();
        $cursor->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild($modifiedDateBlock);
        return true;
    }

    /**
     * Render modified date
     *
     * @param string $pageName Page name
     *
     * @return HtmlBlock
     */
    private function renderModifiedDate($pageName): HtmlBlock
    {
        $fileName = $this->getFileName($pageName);
        $modifiedDate = $this->service->getModifiedDate($fileName);
        $modifiedDateBlock = new HtmlBlock(HtmlBlock::TYPE_1_CODE_CONTAINER);

        if ($modifiedDate instanceof \DateTime) {
            $modifiedDateText = $modifiedDate->format($this->dateFormat);
            $modifiedDateBlock->setLiteral(
                $this->renderer->render('modified-date', ['modifiedDate' => $modifiedDateText])
            );
        }

        return $modifiedDateBlock;
    }

    /**
     * Get remote file name from page name
     *
     * @param string $pageName Page name
     *
     * @return string
     */
    private function getFileName(string $pageName): string
    {
        $templateDetails = $this->pageLocator->determineTemplateAndRenderer('templates/content/', $pageName);

        return $templateDetails['page'] . '.md';
    }
}
