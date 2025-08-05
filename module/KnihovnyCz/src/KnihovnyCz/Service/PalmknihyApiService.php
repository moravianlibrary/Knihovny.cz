<?php

declare(strict_types=1);

namespace KnihovnyCz\Service;

use KnihovnyCz\Date\Converter as DateConverter;
use KnihovnyCz\RecordDriver\SolrDefault;
use VuFind\Db\Entity\UserEntityInterface;
use VuFindHttp\HttpServiceAwareInterface;
use VuFindHttp\HttpServiceAwareTrait;

/**
 * API service for Palmknihy checkouts.
 *
 * @category VuFind
 * @package  Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 */
class PalmknihyApiService implements HttpServiceAwareInterface
{
    use HttpServiceAwareTrait;

    protected array $defaultConfig = [];

    /**
     * Constructor.
     *
     * @param array         $palmknihyConfig Palmknihy configuration
     * @param DateConverter $dateConverter   Date converter service
     */
    public function __construct(protected array $palmknihyConfig, protected DateConverter $dateConverter)
    {
        if ($this->palmknihyConfig['default'] ?? false) {
            $this->defaultConfig = $this->palmknihyConfig['default'] ?? [];
            unset($this->palmknihyConfig['default']);
        }
        $this->filterConfigByInstitution();
    }

    /**
     * Check if the user can borrow an ebook. Return array of errors if there is any issue.
     *
     * @param array               $patron Patron data
     * @param UserEntityInterface $user   User entity
     * @param SolrDefault         $record Record to check
     * @param string              $source Source identifier
     *
     * @return array
     */
    public function checkBeforeLending(
        array $patron,
        UserEntityInterface $user,
        SolrDefault $record,
        string $source
    ): array {
        $palmknihyEnabled = $this->isPalmknihyEnabled($source);
        $booksEnabled = $this->isPalmknihyBooksEnabled($source);
        $audiobooksEnabled = $this->isPalmknihyAudiobooksEnabled($source);
        $email = $patron['email'];
        $errors = [];
        try {
            $expireDate = $this->dateConverter->parseDisplayDate($patron['expiration_date']);
            $today = new \DateTime();

            if ($expireDate === false || $expireDate <= $today) {
                $errors[] = 'palmknihy_error_patron_account_expired';
            }
        } catch (\Exception $e) {
            $errors[] = 'palmknihy_error_patron_expiration_date_could_not_be_parsed';
        }

        if (!$this->isPalmknihyEnabled($source)) {
            $errors[] = 'palmknihy_error_lending_disabled';
        }
        if ($record->tryMethod('getPalmknihyPrice', [], 0) > $this->getPalmknihyMaxPrice($source)) {
            $errors[] = 'palmknihy_error_price_over_limit';
        }
        if ($palmknihyEnabled && $record->tryMethod('isPalmknihyBook') && !$booksEnabled) {
            $errors[] = 'palmknihy_error_books_lending_disabled';
        }
        if ($palmknihyEnabled && $record->tryMethod('isPalmknihyAudioBook') && !$audiobooksEnabled) {
            $errors[] = 'palmknihy_error_audiobooks_lending_disabled';
        }
        if ($user->getActivePalmknihyCheckoutsCount($email, $source) >= $this->getPalmknihyMaxCheckouts($source)) {
            $errors[] = 'palmknihy_error_too_many_issues';
        }
        if ($user->hasSamePalmknihyCheckout($email, $record, $source)) {
            $errors[] = 'palmknihy_error_duplicated_loan';
        }
        if ($record->tryMethod('getPalmknihyId') === null) {
            $errors[] = 'palmknihy_error_record_id_not_found';
        }

        return $errors;
    }

    /**
     * Lend an ebook to the user.
     *
     * @param SolrDefault $record Record to lend
     * @param string      $email  User email
     * @param string      $source Source identifier
     *
     * @return array Array of errors and debug information
     */
    public function lendEbook(SolrDefault $record, string $email, string $source): array
    {
        $errors = [];
        $lib = $this->getPalmknihyLibId($source);
        $key = $this->getPalmknihyKey($source);
        $palmknihyId = $record->tryMethod('getPalmknihyId');

        $method = 'POST';
        $time = dechex(time());
        $apiUrl = $this->getPalmknihyApiUrl($source);
        $urlRequest = $this->getPalmknihyApiEndpoint($source);
        $body = [
            'user' => $email,
            'count' => 1,
            'ebook' => $palmknihyId,
        ];
        $body = http_build_query($body);
        $data = $method . $urlRequest . $body;
        $time_key = hash_hmac('sha256', $lib . $time, $key, true);
        $hmac = hash_hmac('sha256', $data, $time_key, true);
        $authorization = $lib . ':' . $time . ':' . base64_encode($hmac);

        $client = $this->httpService->createClient($apiUrl . $urlRequest);
        $client->setMethod($method);
        $headers = [
            'Accept-language' => 'cs',
            'Accept' => 'text/xml;version=*',
            'Accept-Encoding' => 'gzip',
            'Content-type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'AB-HLIB ' . $authorization,
        ];
        $client->setHeaders($headers);
        $client->setRawBody($body);
        $response = $client->send();
        $httpStatus = $response->getStatusCode();
        $responseContent = $response->getBody();

        $xml = simplexml_load_string($responseContent);
        if ($httpStatus !== 200) {
            $errorsList = [
                401 => 'palmknihy_error_authentication',
                403 => 'palmknihy_error_permissions',
                404 => 'palmknihy_error_object_not_found',
                405 => 'palmknihy_error_method_not_supported',
                409 => 'palmknihy_error_duplicated_loan',
                413 => 'palmknihy_error_too_many_requests',
                415 => 'palmknihy_error_bad_type',
                416 => 'palmknihy_error_bad_pagination',
                417 => 'palmknihy_error_missing_requirements',
            ];
            $errors[] = $errorsList[$httpStatus] ?? 'Error code ' . $httpStatus;
            $debug = ': ' . $xml->{'dev_message'};
        }
        return  [$errors, $debug ?? ''];
    }

    /**
     * Filter out the not configured institutions from the user card prefixes.
     *
     * @param array $prefixes User card prefixes
     *
     * @return array
     */
    public function getEnabledPrefixes(array $prefixes): array
    {
        return array_filter($prefixes, fn ($prefix) => $this->isPalmknihyEnabled($prefix));
    }

    /**
     * Filter out the not configured institutions from the user card prefixes for books.
     *
     * @param array $prefixes User card prefixes
     *
     * @return array
     */
    public function getEnabledPrefixesForBooks(array $prefixes): array
    {
        return array_filter($prefixes, fn ($prefix) => $this->isPalmknihyBooksEnabled($prefix));
    }

    /**
     * Filter out the not configured institutions from the user card prefixes for audiobooks.
     *
     * @param array $prefixes User card prefixes
     *
     * @return array
     */
    public function getEnabledPrefixesForAudiobooks(array $prefixes): array
    {
        return array_filter($prefixes, fn ($prefix) => $this->isPalmknihyAudiobooksEnabled($prefix));
    }

    /**
     * Get configuration for libraries with books enabled.
     *
     * @return array
     */
    public function getEnabledConfigForBooks(): array
    {
        return array_filter($this->palmknihyConfig, fn ($config) => $config['books_enabled'] ?? false);
    }

    /**
     * Get configuration for libraries with audio books enabled.
     *
     * @return array
     */
    public function getEnabledConfigForAudioBooks(): array
    {
        return array_filter($this->palmknihyConfig, fn ($config) => $config['audiobooks_enabled'] ?? false);
    }

    /**
     * Get Palmknihy API URL.
     *
     * @param string $sourceId Source identifier
     *
     * @return string
     */
    public function getPalmknihyApiUrl(string $sourceId): string
    {
        return $this->getPalmknihyConfigField('api_url', $sourceId);
    }

    /**
     * Get Palmknihy API endpoint.
     *
     * @param string $sourceId Source identifier
     *
     * @return string
     */
    public function getPalmknihyApiEndpoint(string $sourceId): string
    {
        return $this->getPalmknihyConfigField('endpoint', $sourceId);
    }

    /**
     * Get Palmknihy library identifier.
     *
     * @param string $sourceId Source identifier
     *
     * @return string
     */
    public function getPalmknihyLibId(string $sourceId): string
    {
        return $this->getPalmknihyConfigField('library_id', $sourceId);
    }

    /**
     * Get Palmknihy API key.
     *
     * @param string $sourceId Source identifier
     *
     * @return string
     */
    public function getPalmknihyKey(string $sourceId): string
    {
        return $this->getPalmknihyConfigField('key', $sourceId);
    }

    /**
     * Is Palkmnihy lending service enabled for the given institution?
     *
     * @param string $sourceId Source identifier
     *
     * @return bool
     */
    public function isPalmknihyEnabled(string $sourceId): bool
    {
        return $this->isPalmknihyBooksEnabled($sourceId) || $this->isPalmknihyAudiobooksEnabled($sourceId);
    }

    /**
     * Is Palmknihy books lending enabled for the given institution?
     *
     * @param string $sourceId Source identifier
     *
     * @return bool
     */
    public function isPalmknihyBooksEnabled(string $sourceId): bool
    {
        return (bool)$this->getPalmknihyConfigField('books_enabled', $sourceId);
    }

    /**
     * Is Palmknihy audiobooks lending enabled for the given institution?
     *
     * @param string $sourceId Source identifier
     *
     * @return bool
     */
    public function isPalmknihyAudiobooksEnabled(string $sourceId): bool
    {
        return (bool)$this->getPalmknihyConfigField('audiobooks_enabled', $sourceId);
    }

    /**
     * Get Palmknihy maximum checkouts.
     *
     * @param string $sourceId Source identifier
     *
     * @return int
     */
    public function getPalmknihyMaxCheckouts(string $sourceId): int
    {
        return intval($this->getPalmknihyConfigField('max_checkouts', $sourceId)) ?? 3;
    }

    /**
     * Get Palmknihy lending interval.
     *
     * @param string $sourceId Source identifier
     *
     * @return int
     */
    public function getPalmknihyLendingInterval(string $sourceId): int
    {
        return intval($this->getPalmknihyConfigField('lending_interval', $sourceId)) ?? 21;
    }

    /**
     * Get Palmknihy maximum price.
     *
     * @param string $sourceId Source identifier
     *
     * @return int
     */
    public function getPalmknihyMaxPrice(string $sourceId): int
    {
        return intval($this->getPalmknihyConfigField('max_price', $sourceId)) ?? 49;
    }

    /**
     * Get Palmknihy information URL.
     *
     * @param string $sourceId Source identifier
     *
     * @return string|null
     */
    public function getPalmknihyInfoUrl(string $sourceId): ?string
    {
        return $this->getPalmknihyConfigField('info_url', $sourceId);
    }

    /**
     * Get Palmknihy configuration field.
     *
     * @param string $field    Field name
     * @param string $sourceId Source identifier
     *
     * @return string|null
     */
    protected function getPalmknihyConfigField(string $field, string $sourceId): ?string
    {
        return $this->palmknihyConfig[$sourceId][$field] ?? null;
    }

    /**
     * Filter the Palmknihy configuration by institution.
     *
     * This method modifies the $palmknihyConfig property to only include
     * the configuration for the specified institution if it is set.
     *
     * @return void
     */
    protected function filterConfigByInstitution(): void
    {
        $institution = $this->defaultConfig['institution'] ?? '';
        if (!empty($institution) && !empty($this->palmknihyConfig[$institution])) {
            $this->palmknihyConfig = [
                $institution => $this->palmknihyConfig[$institution],
            ];
        }
    }

    /**
     * Check if the service is configured for a single institution.
     *
     * @return bool
     */
    public function isSingleInstitution(): bool
    {
        return isset($this->defaultConfig['institution']);
    }
}
