<?php declare(strict_types=1);

/**
 * Class ObalkyKnih
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2019.
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
 * @package  KnihovnyCz\Content\Covers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */


//TODO: implement cache (in service class)
namespace KnihovnyCz\Content\Covers;

class ObalkyKnih extends \VuFind\Content\AbstractCover
    implements \VuFindHttp\HttpServiceAwareInterface, \Zend\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;
    //TODO: reafactor to service class
    use \VuFindHttp\HttpServiceAwareTrait;
    //TODO: refactor to service class
    use \VuFind\ILS\Driver\CacheTrait;

    /**
     * API URL
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * Constructor
     */
    public function __construct($config)
    {
        $this->supportsIsbn = true;
        $this->supportsIssn = true;
        $this->supportsOclc = true;
        $this->supportsUpc = true;
        $this->cacheAllowed = false;

        //TODO: refactor to service class
        $this->apiUrl = $config->base_url1 . $config->books_endpoint;
        $this->cacheLifetime = 1800;
    }

    /**
     * Get an HTTP client
     *
     * @param string $url URL for client to use
     *
     * @return \Zend\Http\Client
     */
    // TODO: refactor to service class
    protected function getHttpClient($url = null)
    {
        if (null === $this->httpService) {
            throw new \Exception('HTTP service missing.');
        }
        return $this->httpService->createClient($url);
    }

    /**
     * Get image URL for a particular API key and set of IDs (or false if invalid).
     *
     * @param string $key  API key
     * @param string $size Size of image to load (small/medium/large)
     * @param array  $ids  Associative array of identifiers (keys may include 'isbn'
     * pointing to an ISBN object and 'issn' pointing to a string)
     *
     * @return string|bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getUrl($key, $size, $ids)
    {
        $data = $this->getData($ids);
        if (!isset($data)) {
            return false;
        }
        switch ($size) {
        case 'small':
            $imageUrl = $data->cover_icon_url;
            break;
        case 'medium':
            $imageUrl = $data->cover_medium_url;
            break;
        case 'large':
            $imageUrl = $data->cover_preview510_url;
            break;
        default:
            $imageUrl = $data->cover_medium_url;
            break;
        }
        return $imageUrl;
    }

    /* TODO: refactor to service class */
    public function getData($ids): ?\stdClass
    {
        $cachedData = $this->getCachedData($ids['recordid']);
        if ($cachedData === null) {
            $cachedData = $this->getFromService($ids);
            $this->putCachedData($ids['recordid'], $cachedData);
        }
        return $cachedData;
    }

    /* TODO refactor to service class */
    protected function getFromService($ids): ?\stdClass {
        $param = "multi";
        $query = [];
        $isbn = $ids['isbn'] ? $ids['isbn']->get13() : null;
        $isbn = $isbn ?? $ids['upc'] ?? $ids['issn'] ?? null;
        $oclc = $ids['oclc'] ?? null;
        $ismn = $ids['ismn'] ?? null;
        $nbn = $ids['nbn'] ?? null;

        foreach(['isbn', 'oclc', 'ismn', 'nbn' ] as $identifier) {
            if (isset($$identifier)) {
                $query[$identifier] = $$identifier;
            }
        }
        $url = $this->apiUrl . "?";
        $url .= http_build_query([$param => json_encode([$query])]);
        $response = $this->getHttpClient($url)->send();
        return $response->isSuccess() ? json_decode($response->getBody())[0]: null;
    }
}