<?php
declare(strict_types=1);

/**
 * Factory for the default SOLR backend.
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
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace KnihovnyCz\Search\Factory;

use KnihovnyCz\Search\Solr\ChildDocDeduplicationListener;
use KnihovnyCz\Search\Solr\DeduplicationListener;
use VuFind\Search\Factory\SolrDefaultBackendFactory
    as ParentSolrDefaultBackendFactory;
use VuFindSearch\Backend\Solr\Backend;

/**
 * Factory for the default SOLR backend.
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class SolrDefaultBackendFactory extends ParentSolrDefaultBackendFactory
{
    /**
     * Get a deduplication listener for the backend
     *
     * @param Backend $backend Search backend
     * @param bool    $enabled Whether deduplication is enabled
     *
     * @return DeduplicationListener
     */
    protected function getDeduplicationListener(
        Backend $backend,
        $enabled
    ) {
        $class = DeduplicationListener::class;
        $search = $this->config->get($this->searchConfig);
        $type = $search->Records->deduplication_type ?? null;
        if ($type == 'child') {
            $class = ChildDocDeduplicationListener::class;
        }
        return new $class(
            $backend,
            $this->serviceLocator,
            $this->searchConfig,
            $this->facetConfig,
            'datasources',
            $enabled
        );
    }
}
