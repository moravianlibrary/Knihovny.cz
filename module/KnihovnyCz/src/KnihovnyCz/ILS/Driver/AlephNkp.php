<?php

declare(strict_types=1);

namespace KnihovnyCz\ILS\Driver;

use VuFind\Exception\ILS as ILSException;

/**
 * Class AlephNkp
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AlephNkp extends Aleph
{
    protected ?string $cgiScriptBase;

    /**
     * Initialize the driver.
     *
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     *
     * @throws ILSException
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->cgiScriptBase = $this->config['Catalog']['cgiScriptBase'] ?? null;
    }

    /**
     * Helper method to determine whether or not a certain method can be
     * called on this driver.  Required method for any smart drivers.
     *
     * @param string $method The name of the called method.
     * @param array  $params Array of passed parameters
     *
     * @return bool True if the method can be called with the given parameters,
     * false otherwise.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsMethod($method, $params)
    {
        if (in_array($method, ['getMyProlongRegistrationLinkText', 'getMyPaymentLinkText'])) {
            return !empty($this->cgiScriptBase);
        }
        return parent::supportsMethod($method, $params);
    }

    /**
     * Get link for prolonging of registration with all notes returned from ILS - used for nkp
     *
     * @param array $patron patron
     *
     * @return string|null
     */
    public function getMyProlongRegistrationLinkText(array $patron): ?string
    {
        return $this->getRenewOrPayLinkText('renew', $patron['id']);
    }

    /**
     * Get link for paying fees with all notes returned from ILS - used for nkp
     *
     * @param array $patron patron
     *
     * @return string|null
     */
    public function getMyPaymentLinkText(array $patron): ?string
    {
        return $this->getRenewOrPayLinkText('pay', $patron['id']);
    }

    /**
     * Get link to cgi script for prolong registration and payment information
     *
     * @param string $function Function to call - renew or pay
     * @param string $patronId Patron id
     *
     * @return string|null
     */
    protected function getRenewOrPayLinkText(string $function, string $patronId): ?string
    {
        $functions = [
            'renew' => 'bor_renew_online',
            'pay'   => 'bor_cash_online',
        ];
        if (empty($patronId) || empty($function) || !in_array($function, array_keys($functions))) {
            return null;
        }
        $locale = $this->translator->getLocale();
        $lang = $this->languages[$locale] ?? 'cze';
        $url = $this->cgiScriptBase
            . $functions[$function] . '?'
            . http_build_query(['id' => $patronId, 'ln' => $lang]);
        $client = $this->httpService->createClient($url);
        $response = $client->send();
        if ($response->getStatusCode() === 200) {
            $text = $response->getContent();
            $text = preg_replace('/<br>/', '', $text);
            $parts = explode('||', $text);
            if (!in_array(count($parts), [1, 4])) {
                return null;
            }
            if (count($parts) === 1) {
                return $parts[0];
            }
            $textParts = [];
            $link = array_shift($parts);
            if (!empty($link)) {
                $textParts[] = $link;
            }
            $parts = array_map(fn ($part) => floatval(trim($part)), $parts);
            if ($parts[2] != 0) {
                $textParts[] = $this->translate('ILSMessages::not_closed_fee_all', ['%%amount%%' => $parts[2]]);
            }
            $textParts = array_map(fn ($part) => rtrim($part, ".\n"), $textParts);
            return implode('. ', $textParts) . '.';
        }
        return null;
    }
}
