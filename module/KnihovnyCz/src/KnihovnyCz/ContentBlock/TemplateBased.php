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
     * Name of template for rendering
     *
     * @var string
     */
    protected $templateName;

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
        return [
            'templateName' => $this->templateName,
        ];
    }
}