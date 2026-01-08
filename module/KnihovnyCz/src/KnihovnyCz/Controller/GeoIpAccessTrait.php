<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\Mvc\MvcEvent;

/**
 * Trait GeoIpAccessTrait
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait GeoIpAccessTrait
{
    /**
     * Check GeoIP country code
     *
     * @param MvcEvent $e Event object
     *
     * @return mixed
     */
    public function checkGeoIP(MvcEvent $e)
    {
        $config = $this->getConfig('Summon');
        $allowedCountries = isset($config->Access_Geo->allowed_geoip_countries)
            ? array_map('trim', explode(',', $config->Access_Geo->allowed_geoip_countries))
            : [];

        if (empty($allowedCountries)) {
            return;
        }

        $request = $this->getRequest();
        $headers = $request->getHeaders();
        if ($headers->has('x-geoip-country-code')) {
            $countryCode = $headers->get('x-geoip-country-code')->getFieldValue();
            if (!in_array($countryCode, $allowedCountries)) {
                $response = $this->getResponse();
                $response->setStatusCode(403);
                $response->setContent('Forbidden: Access denied for your country.');
                return $response;
            }
        }
    }
}
