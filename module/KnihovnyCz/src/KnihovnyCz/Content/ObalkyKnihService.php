<?php

namespace KnihovnyCz\Content;

/**
 * Class ObalkyKnihService
 *
 * @category VuFind
 * @package  KnihovnyCz\Content
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ObalkyKnihService extends \VuFind\Content\ObalkyKnihService
{
    /**
     * Default timeout for ObalkyKnih.cz check alive
     */
    protected const DEFAULT_TIMEOUT = 2;

    /**
     * Expected response from obalkyknih.cz alive check
     */
    protected const OBALKYKNIH_ALIVE_RESPONSE = 'ALIVE';

    /**
     * Obalky knih checker
     *
     * @var string
     */
    protected $checkerUrl = '';

    /**
     * Constructor
     *
     * @param \Vufind\Config\Config $config Configuration for service
     */
    public function __construct(\Vufind\Config\Config $config)
    {
        parent::__construct($config);
        if (!isset($config->authority_endpoint)) {
            throw new \Exception(
                'Configuration for ObalkyKnih.cz service is not valid'
            );
        }

        $this->checkerUrl = $config->checkerUrl ?? '';
    }

    /**
     * Get data from service
     *
     * @param array $ids Record identifiers
     *
     * @return \stdClass|null
     * @throws \Exception
     */
    protected function getFromService(array $ids): ?\stdClass
    {
        if (isset($ids['nbn']) && substr($ids['recordid'] ?? '', 0, 4) === 'auth') {
            return $this->getAuthorityFromService($ids['nbn']);
        } else {
            return parent::getFromService($ids);
        }
    }

    /**
     * Get obalkyknih metadata for authority from external service
     *
     * @param string $authId Authority record identifier
     *
     * @return \stdClass|null
     * @throws \Exception
     */
    protected function getAuthorityFromService(string $authId)
    {
        $url = $this->getBaseUrl();
        if ($url === '') {
            $this->logWarning('All ObalkyKnih servers are down.');
            return null;
        }
        $url .= $this->endpoints['authority'] . '/meta?';
        $url .= http_build_query(['auth_id' => $authId]);
        $client = $this->getHttpClient($url);
        $client->setOptions(['timeout' => 1]);
        try {
            $response = $client->send();
        } catch (\Exception $e) {
            $this->logError('Unexpected ' . $e::class . ': ' . $e->getMessage());
            return null;
        }
        return $response->isSuccess() ? json_decode($response->getBody())[0] : null;
    }

    /**
     * Check base URLs and return the first available
     *
     * @return string
     */
    protected function getAliveUrl(): string
    {
        return $this->getAliveUrlFromChecker() ?? $this->getAliveUrlFromObalkyKnih() ?? '';
    }

    /**
     * Get live URL from checker
     *
     * @return string|null
     */
    protected function getAliveUrlFromChecker(): ?string
    {
        if (empty($this->checkerUrl)) {
            return null;
        }
        $aliveUrl = $this->getCachedData('aliveUrl');
        if ($aliveUrl !== null) {
            return $aliveUrl;
        }
        try {
            $client = $this->getHttpClient($this->checkerUrl);
            $client->setOptions(['timeout' => self::DEFAULT_TIMEOUT]);
            $response = $client->send();
        } catch (\Exception $e) {
            $this->logError('Unexpected ' . $e::class . ': ' . $e->getMessage());
            return null;
        }
        if (!$response->isSuccess()) {
            return null;
        }
        $body = $response->getBody();
        if (str_starts_with($body, '"') && str_ends_with($body, '"')) {
            $aliveUrl = substr($body, 1, -1);
            if (filter_var($aliveUrl, FILTER_VALIDATE_URL) !== false) {
                return $aliveUrl;
            }
        }
        return null;
    }

    /**
     * Check base URLs and return the first available
     *
     * @return string|null
     */
    protected function getAliveUrlFromObalkyKnih(): ?string
    {
        $aliveUrl = $this->getCachedData('baseUrl');
        if ($aliveUrl !== null) {
            return $aliveUrl;
        }
        foreach ($this->baseUrls as $baseUrl) {
            $url = $baseUrl . $this->endpoints['alive'];
            try {
                $client = $this->getHttpClient($url);
                $client->setOptions(['timeout' => self::DEFAULT_TIMEOUT]);
                $response = $client->send();
            } catch (\Exception $e) {
                $this->logError('Unexpected ' . $e::class . ': ' . $e->getMessage());
                continue;
            }
            if ($response->isSuccess() && trim($response->getBody()) == self::OBALKYKNIH_ALIVE_RESPONSE) {
                $this->putCachedData('baseUrl', $baseUrl, 60);
                return $baseUrl;
            }
        }
        return null;
    }
}
