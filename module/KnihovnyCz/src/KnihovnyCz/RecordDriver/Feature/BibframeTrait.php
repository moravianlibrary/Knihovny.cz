<?php

/**
 * Trait BibframeTrait
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
 * @package  KnihovnyCz\RecordDriver\Feature
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\RecordDriver\Feature;

use VuFind\XSLT\Processor as XSLTProcessor;

/**
 * Trait BibframeTrait
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDriver\Feature
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait BibframeTrait
{
    /**
     * Get an XML RDF representation of the data in this record using BIBFRAME
     * and MADS ontologies
     *
     * @return mixed XML RDF data (empty if unsupported or error).
     */
    public function getBibframeRdfXml()
    {
        return XSLTProcessor::process(
            'marc2bibframe2.xsl',
            trim($this->getMarcReader()->toFormat('MARCXML')),
            [
                'baseuri' => 'https://www.knihovny.cz/Record/',
                'id' => $this->getUniqueID(),
            ]
        );
    }
}
