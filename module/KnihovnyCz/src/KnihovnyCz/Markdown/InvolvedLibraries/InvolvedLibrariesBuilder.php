<?php
declare(strict_types=1);

/**
 * Class InvolvedLibrariesBuilder
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
namespace KnihovnyCz\Markdown\InvolvedLibraries;

use KnihovnyCz\Content\InvolvedLibrariesService;
use KnihovnyCz\Markdown\InvolvedLibraries\Node\InvolvedLibraries;
use KnihovnyCz\Markdown\InvolvedLibraries\Node\InvolvedLibrariesPlaceholder;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListData;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\NodeIterator;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

/**
 * Class InvolvedLibrariesBuilder
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Markdown\InvolvedLibraries
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InvolvedLibrariesBuilder implements ConfigurationAwareInterface
{
    protected ConfigurationInterface $config;

    protected InvolvedLibrariesService $service;

    /**
     * Contructor
     *
     * @param InvolvedLibrariesService $service Involved libraries service
     */
    public function __construct(InvolvedLibrariesService $service)
    {
        $this->service = $service;
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
     * @return InvolvedLibraries
     */
    protected function generate(): InvolvedLibraries
    {
        $listData = new ListData();
        $listData->type = ListBlock::TYPE_BULLET;
        $involvedLibraries = new InvolvedLibraries();
        $libraries = $this->service->getInvolvedLibraries();
        foreach ($libraries as $region => $regionalLibraries) {
            $heading = new Heading(3);
            $text = new Text();
            $text->append($region);
            $heading->appendChild($text);
            $involvedLibraries->appendChild($heading);
            $list = new ListBlock($listData);
            foreach ($regionalLibraries as $library) {
                $link = new Link(
                    '/LibraryRecord/' . $library['id'],
                    $library['name']
                );
                $listItem = new ListItem($listData);
                $listItem->appendChild($link);
                $list->appendChild($listItem);
            }
            $involvedLibraries->appendChild($list);
        }
        // Add custom CSS class
        $involvedLibraries->data->append('attributes/class', 'involved-libraries');
        return $involvedLibraries;
    }

    /**
     * Replace placeholder with generated node tree
     *
     * @param Document $document          Main document
     * @param callable $librariesFunction Function which generates node tree
     *
     * @return void
     */
    protected function replacePlaceholders(
        Document $document,
        callable $librariesFunction
    ): void {
        foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            // Add the block once we find a placeholder
            if (! $node instanceof InvolvedLibrariesPlaceholder) {
                continue;
            }
            $libraries = $librariesFunction();
            if ($libraries !== null) {
                $node->replaceWith(clone $libraries);
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
