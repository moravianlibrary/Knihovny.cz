<?php
declare(strict_types=1);

/**
 * Factory for the default EDS backend.
 *
 * PHP version 7
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
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace KnihovnyCz\Search\Factory;

use KnihovnyCz\Search\EDS\Backend\Connector;
use VuFind\Search\Factory\EdsBackendFactory
    as ParentEdsBackendFactory;

/**
 * Factory for the default EDS backend.
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class EdsBackendFactory extends ParentEdsBackendFactory
{
    /**
     * Create the EDS connector.
     *
     * @return Connector
     */
    protected function createConnector()
    {
        $options = [
            'timeout' => $this->edsConfig->General->timeout ?? 120,
            'search_http_method' => $this->edsConfig->General->search_http_method
                ?? 'POST'
        ];
        if (isset($this->edsConfig->General->api_url)) {
            $options['api_url'] = $this->edsConfig->General->api_url;
        }
        if (isset($this->edsConfig->General->auth_url)) {
            $options['auth_url'] = $this->edsConfig->General->auth_url;
        }
        // Build HTTP client:
        $client = $this->serviceLocator->get(\VuFindHttp\HttpService::class)
            ->createClient();
        $connector = new Connector($options, $client);
        $connector->setLogger($this->logger);
        return $connector;
    }
}
