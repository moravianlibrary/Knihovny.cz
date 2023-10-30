<?php

declare(strict_types=1);

namespace KnihovnyCz\Service;

use Laminas\Config\Config;

/**
 * Class CitaceProService
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class CitaceProService implements \VuFindHttp\HttpServiceAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Configuration
     */
    protected Config $config;

    /*
     * Default citation style
     */
    protected string $defaultCitationStyle;

    /**
     * CitaceProService constructor.
     *
     * @param Config $config               Citation       configuration
     * @param string $defaultCitationStyle default citation style
     */
    public function __construct(Config $config, $defaultCitationStyle)
    {
        $this->config = $config;
        $this->defaultCitationStyle = $defaultCitationStyle;
    }

    /**
     * Get citation style
     *
     * @param string      $recordId Record identifier
     * @param string|null $style    Citation style
     *
     * @return string Generated citation as HTML snippet
     * @throws \Exception
     */
    public function getCitation(string $recordId, ?string $style = null): string
    {
        $source = 'Solr';
        if (str_contains($recordId, '|')) {
            [$source, $recordId] = explode('|', $recordId);
        }
        if ($source != 'Solr') {
            throw new \Exception('Citation not found');
        }
        $style = $style && $this->isCitationStyleValid($style)
            ? $style : $this->getDefaultCitationStyle();

        $query = [
            'citacniStyl' => $style,
        ];
        $citationServerUrl = 'https://www.citacepro.com/api/cpk/citace/'
            . urlencode($recordId) . '?' . http_build_query($query);
        $http = $this->httpService->createClient($citationServerUrl, 'GET');
        $response = $http->send();
        if ($response->getStatusCode() != 200) {
            throw new \Exception('Citation not found');
        }
        $doc = new \DOMDocument();
        $doc->loadHTML($response->getBody(), LIBXML_NOERROR);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//*[@id="citace"]');
        if ($results === false || !$item = $results->item(0)) {
            throw new \Exception('Citation not found');
        }
        $citation = $item->c14n();
        return $citation;
    }

    /**
     * Get link to citacepro.com
     *
     * @param string $recordId Record identifier
     *
     * @return string
     */
    public function getCitationLink(string $recordId): string
    {
        return 'https://www.citacepro.com/nacist-dokument-sysno/'
            . $recordId . '?katalog=cpk';
    }

    /**
     * Get available citation styles
     *
     * @return array
     */
    public function getCitationStyles(): array
    {
        return $this->config->Citation->citation_styles->toArray() ?? [];
    }

    /**
     * Validates citation style
     *
     * @param string $style Citation style code
     *
     * @return bool
     */
    protected function isCitationStyleValid(string $style): bool
    {
        return array_key_exists(
            $style,
            $this->getCitationStyles() ?? []
        );
    }

    /**
     * Get default citation style
     *
     * @return string
     */
    public function getDefaultCitationStyle(): string
    {
        return $this->defaultCitationStyle;
    }
}
