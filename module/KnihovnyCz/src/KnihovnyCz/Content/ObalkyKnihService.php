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
     * Obalky knih checker
     *
     * @var string
     */
    protected $checkerUrl = '';

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config $config Configuration for service
     */
    public function __construct(\Laminas\Config\Config $config)
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
        if (empty($this->checkerUrl)) {
            return parent::getAliveUrl();
        }
        $aliveUrl = $this->getCachedData('aliveUrl');
        if ($aliveUrl !== null) {
            return $aliveUrl;
        }
        $client = $this->getHttpClient($this->checkerUrl);
        $response = $client->send();
        $client->setOptions(['timeout' => 1]);
        if ($response->isSuccess()) {
            $aliveUrl = trim($response->getBody(), '/"');
            $this->putCachedData('aliveUrl', $aliveUrl, 30);
            return $aliveUrl;
        }
        return $this->baseUrls[0];
    }
}
