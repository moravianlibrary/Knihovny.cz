<?php

/**
 * Class TemplateBased
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
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
 * @category VuFind
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\ContentBlock;

class TemplateBased implements \VuFind\ContentBlock\ContentBlockInterface
{
    /**
     * Types/formats of content
     *
     * @var array $types
     */
    protected $types = [
        'phtml',
        'md',
    ];

    /**
     * Theme info service
     *
     * @var \VuFindTheme\ThemeInfo
     */
    protected $themeInfo;

    /**
     * Translator
     *
     * @var \Laminas\Mvc\I18n\Translator
     */
    protected $translator;

    /**
     * Default langugae
     *
     * @var string
     */
    protected $defaultLanguage;

    /**
     * Name of template for rendering
     *
     * @var string
     */
    protected $templateName;

    /**
     * TemplateBased constructor.
     *
     * @param \VuFindTheme\ThemeInfo       $theme      Theme info
     * @param \Laminas\Mvc\I18n\Translator $translator Translator
     * @param \Laminas\Config\Config       $config     Main config
     */
    public function __construct(\VuFindTheme\ThemeInfo $theme,
        \Laminas\Mvc\I18n\Translator $translator, \Laminas\Config\Config $config
    ) {
        $this->themeInfo = $theme;
        $this->translator = $translator;
        $this->defaultLanguage  = $config->Site->language;
    }

    /**
     * @inheritDoc
     */
    public function setConfig($settings)
    {
        $this->templateName = $settings;
    }

    /**
     * @inheritDoc
     */
    public function getContext()
    {
        $page = $this->templateName;
        $language = $this->translator->getLocale();
        $templates = [
            "{$page}_$language",
            "{$page}_$this->defaultLanguage",
            $page,
        ];
        $pathPrefix = "templates/ContentBlock/TemplateBased/";

        foreach ($templates as $template) {
            foreach ($this->types as $type) {
                $filename = "$pathPrefix$template.$type";
                $path = $this->themeInfo->findContainingTheme($filename, true);
                if (null != $path) {
                    $page = $template;
                    $renderer = $type;
                    break 2;
                }
            }
        }
        $method = isset($renderer) ? 'getContextFor' . ucwords($renderer) : false;

        return $method && is_callable([$this, $method])
            ? $this->$method($page, $path)
            : [];
    }

    /**
     * Return context array for markdown
     *
     * @param string $page Page name
     * @param string $path Full path of file
     *
     * @return array
     */
    protected function getContextForMd(string $page, string $path): array
    {
        return [
            'templateName' => 'markdown',
            'data' => file_get_contents($path),
        ];
    }

    /**
     * Return context array of phtml
     *
     * @param string $page Page name
     * @param string $path Full path of fie
     *
     * @return array
     */
    protected function getContextForPhtml(string $page, string $path): array
    {
        return [
            'templateName' => $this->templateName,
        ];
    }
}
