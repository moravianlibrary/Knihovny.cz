<?php
/**
 * EBSCO EDS API Connector
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
 * @category EBSCO
 * @package  EBSCO
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
namespace KnihovnyCz\Search\EDS\Backend;

use VuFindSearch\Backend\EDS\ApiException;
use VuFindSearch\Backend\EDS\Connector as Base;

/**
 * EBSCO EDS API Connector
 *
 * @category EBSCO
 * @package  EBSCO
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class Connector extends Base
{
    /**
     * Process EDSAPI response message
     *
     * @param array $input The raw response from EBSCO
     *
     * @throws ApiException
     * @return array       The processed response from EDS API
     */
    protected function process($input)
    {
        //process response.
        $stream = null;
        try {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $input);
            rewind($stream);
            $listener = new JsonListener();
            $parser = new \JsonStreamingParser\Parser($stream, $listener);
            $parser->parse();
            return $listener->getJson();
        } catch (\Exception $e) {
            throw new ApiException(
                'An error occurred when processing EDS Api response: '
                . $e->getMessage()
            );
        } finally {
            if ($stream != null) {
                fclose($stream);
            }
        }
    }
}
