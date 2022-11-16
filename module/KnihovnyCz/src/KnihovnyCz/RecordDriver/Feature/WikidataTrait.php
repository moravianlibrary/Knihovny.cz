<?php
declare(strict_types=1);

/**
 * Trait WikidataTrait
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
namespace KnihovnyCz\RecordDriver\Feature;

/**
 * Trait WikidataTrait
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDriver\Feature
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait WikidataTrait
{
    /**
     * Wikidata sparql connector
     *
     * @var \KnihovnyCz\Wikidata\SparqlService
     */
    protected \KnihovnyCz\Wikidata\SparqlService $sparqlService;

    /**
     * Attach Wikidata SPARQL connector
     *
     * @param \KnihovnyCz\Wikidata\SparqlService $sparqlService SPARQL connector
     *
     * @return void
     */
    public function attachSparqlService(
        \KnihovnyCz\Wikidata\SparqlService $sparqlService
    ): void {
        $this->sparqlService = $sparqlService;
    }
}
