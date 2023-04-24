<?php

/**
 * Class InvolvedLibrariesExtension
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
 * @package  KnihovnyCz\Markdown
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Markdown\InvolvedLibraries;

use KnihovnyCz\Content\InvolvedLibrariesService;
use KnihovnyCz\Markdown\InvolvedLibraries\Node\InvolvedLibraries;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\CommonMark\Renderer\Block\ParagraphRenderer;
use League\Config\ConfigurationBuilderInterface;
use Nette\Schema\Expect;

/**
 * Class InvolvedLibrariesExtension
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Markdown
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InvolvedLibrariesExtension implements ConfigurableExtensionInterface
{
    /**
     * Involved libraries service
     *
     * @var InvolvedLibrariesService
     */
    protected InvolvedLibrariesService $involvedLibrariesService;

    /**
     * Constructor
     *
     * @param InvolvedLibrariesService $service Involved libraries service
     */
    public function __construct(InvolvedLibrariesService $service)
    {
        $this->involvedLibrariesService = $service;
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
            'involved_libraries',
            Expect::structure(
                [
                'placeholder' => Expect::string()->default('[LibrariesList]'),
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
            InvolvedLibraries::class,
            new InvolvedLibrariesRenderer(new ParagraphRenderer())
        );
        $environment->addEventListener(
            DocumentParsedEvent::class,
            [
                new InvolvedLibrariesBuilder($this->involvedLibrariesService),
                'onDocumentParsed'
            ],
            -150
        );
        $environment->addBlockStartParser(
            InvolvedLibrariesParser::blockStartParser(),
            200
        );
    }
}
