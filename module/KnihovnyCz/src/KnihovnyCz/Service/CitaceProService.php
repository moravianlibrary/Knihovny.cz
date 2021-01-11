<?php
declare(strict_types=1);

/**
 * Class CitaceProService
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
 * @package  KnihovnyCz\Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
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

    /**
     * CitaceProService constructor.
     *
     * @param Config $config Citation configuration
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
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
        $style = $style && $this->isCitationStyleValid($style)
            ? $style : $this->getDefaultCitationStyle();

        $query = [
            'server' => $this->getCitationLocalDomain(),
            'citacniStyl' => $style
        ];
        $citationServerUrl = "https://www.citacepro.com/api/cpk/citace/"
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
            $style, $this->getCitationStyles() ?? []
        );
    }

    /**
     * Get default citation style
     *
     * @return string
     */
    public function getDefaultCitationStyle(): string
    {
        return $this->config->Citation->default_citation_style;
    }

    /**
     * Get local domain
     *
     * @return string
     */
    protected function getCitationLocalDomain(): string
    {
        return str_replace(
            "www.", "", $this->config->Citation->citation_local_domain
        );
    }
}
