<?php

declare(strict_types=1);

namespace KnihovnyCz\Service;

use VuFind\Config\Config;
use VuFind\Record\Loader;
use VuFind\RecordDriver\AbstractBase;

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
     * CitaceProService constructor.
     *
     * @param Config $config               Citation configuration
     * @param string $defaultCitationStyle Default citation style
     * @param Loader $recordLoader         Record loader
     */
    public function __construct(
        protected Config $config,
        protected string $defaultCitationStyle,
        protected Loader $recordLoader
    ) {
    }

    /**
     * Get citation style
     *
     * @param string      $recordId  Record identifier
     * @param string|null $style     Citation style
     * @param string|null $source    Record source
     * @param bool        $plainText Whether to return plain text (true) or HTML (false)
     *
     * @return string Generated citation as HTML snippet
     * @throws \Exception
     */
    public function getCitation(
        string $recordId,
        ?string $style = null,
        ?string $source = 'Solr',
        bool $plainText = false
    ): string {
        $record = null;
        if (str_contains($recordId, '|')) {
            [$source, $recordId] = explode('|', $recordId);
        }
        $record = $this->getNotLocalRecord($recordId, $source);
        $style = $style && $this->isCitationStyleValid($style) ? $style : $this->getDefaultCitationStyle();
        $openUrl = $record != null
            ? $record->tryMethod('getOpenUrlLinkForCitations', [$style])
            : $this->getCitationApiUrl($recordId, $style);
        if (empty($openUrl)) {
            throw new \Exception('Citation not found: No function for URL generation');
        }
        $http = $this->httpService->createClient($openUrl, 'GET');
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

        $purifierConfig = \HTMLPurifier_Config::createDefault();
        $purifierConfig->set('Cache.DefinitionImpl', null);
        $htmlAllowed = $plainText ? '' : 'p,b,i,strong,em,ul,ol,li,a[href],br';
        $purifierConfig->set('HTML.Allowed', $htmlAllowed);
        $purifier = new \HTMLPurifier($purifierConfig);

        $purifiedCitation = $purifier->purify($citation);
        return  $plainText ? html_entity_decode($purifiedCitation) : $purifiedCitation;
    }

    /**
     * Get citation from citacepro.com API
     *
     * @param string $recordId Record identifier
     * @param string $style    Citation style
     *
     * @return string
     * @throws \Exception
     */
    protected function getCitationApiUrl(string $recordId, string $style): string
    {
        $query = [
            'citacniStyl' => $style,
        ];
        return 'https://www.citacepro.com/api/cpk/citace/' . urlencode($recordId) . '?' . http_build_query($query);
    }

    /**
     * Get link to citacepro.com
     *
     * @param string      $recordId Record identifier
     * @param string|null $source   Record source identifier (optional)
     *
     * @return string
     */
    public function getCitationLink(string $recordId, ?string $source = 'Solr'): string
    {
        if ($record = $this->getNotLocalRecord($recordId, $source)) {
            $params = $record->tryMethod('getOpenUrlParamsForCitation');
            if (!empty($params)) {
                return 'https://www.citacepro.com/sfx?' . http_build_query($params);
            }
        }
        return 'https://www.citacepro.com/nacist-dokument-sysno/' . $recordId . '?katalog=cpk';
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

    /**
     * Get record not from local Solr
     *
     * @param string      $recordId Record identifier
     * @param string|null $source   Record source identifier (optional)
     *
     * @return AbstractBase|null
     */
    protected function getNotLocalRecord(string $recordId, ?string $source = 'Solr'): ?AbstractBase
    {
        if ($source != 'Solr') {
            return $this->recordLoader->load($recordId, $source);
        }
        return null;
    }
}
