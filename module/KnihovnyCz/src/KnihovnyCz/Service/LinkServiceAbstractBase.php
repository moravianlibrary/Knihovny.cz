<?php

namespace KnihovnyCz\Service;

use VuFindHttp\HttpServiceAwareInterface;
use VuFindHttp\HttpServiceAwareTrait;

/**
 * Abstract class LinkServiceAbstractBase
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
abstract class LinkServiceAbstractBase implements
    LinkServiceInterface,
    HttpServiceAwareInterface
{
    use HttpServiceAwareTrait;

    /**
     * Get data from service as array
     *
     * @param string $url    Base url
     * @param array  $params Parameters
     *
     * @return array
     */
    protected function getDataFromService(string $url, array $params = []): array
    {
        if (!empty($params)) {
            $url = $url . '?' . http_build_query($params);
        }
        $client = $this->httpService->createClient($url);
        $response = $client->send();
        if ($response->getStatusCode() !== 200) {
            return [];
        }
        $body = $response->getBody();
        return empty($body) ? [] : json_decode($body, true);
    }
}
