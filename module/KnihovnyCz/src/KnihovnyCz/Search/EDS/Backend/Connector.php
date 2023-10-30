<?php

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
