<?php

namespace KnihovnyCz\ILS\Driver;

use KnihovnyCz\Date\Converter as DateConverter;
use KnihovnyCz\Db\Table\InstConfigs;
use KnihovnyCz\Db\Table\InstSources;
use KnihovnyCz\ILS\Service\SolrIdResolver;
use VuFind\Auth\ILSAuthenticator;
use VuFind\Config\PluginManager as ConfigManager;
use VuFind\Date\DateException;
use VuFind\Exception\ILS as ILSException;
use VuFind\ILS\Driver\PluginManager;

/**
 * Class MultiBackend
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class MultiBackend extends \VuFind\ILS\Driver\MultiBackend
{
    /**
     * Table gateway to source configuration
     *
     * @var InstConfigs
     */
    protected $instConfigs;

    /**
     * Table gateway to institution source
     *
     * @var InstSources
     */
    protected $instSources;

    /**
     * Id resolver
     *
     * @var SolrIdResolver
     */
    protected SolrIdResolver $idResolver;

    /**
     * Date converter object
     *
     * @var \KnihovnyCz\Date\Converter
     */
    protected $dateConverter = null;

    /**
     * Constructor
     *
     * @param ConfigManager    $configLoader  Configuration loader
     * @param ILSAuthenticator $ilsAuth       ILS authenticator
     * @param PluginManager    $dm            ILS driver manager
     * @param InstConfigs      $instConfigs   Instances configurations
     * @param InstSources      $instSources   Instances names
     * @param SolrIdResolver   $idResolver    Id resolver
     * @param DateConverter    $dateConverter Date converter
     */
    public function __construct(
        ConfigManager $configLoader,
        ILSAuthenticator $ilsAuth,
        PluginManager $dm,
        InstConfigs $instConfigs,
        InstSources $instSources,
        SolrIdResolver $idResolver,
        DateConverter $dateConverter
    ) {
        $this->instConfigs = $instConfigs;
        $this->instSources = $instSources;
        $this->idResolver = $idResolver;
        $this->dateConverter = $dateConverter;
        parent::__construct($configLoader, $ilsAuth, $dm);
    }

    /**
     * Get configuration for the ILS driver.  We load the settings from DB.
     * If no settings found, we will return an empty array.
     *
     * @param string $source The source id to use for determining the
     * configuration
     *
     * @return array   The configuration of the driver
     */
    protected function getDriverConfig($source)
    {
        $instSource = $this->instSources->getSource($source);
        if ($instSource == null) {
            return [];
        }
        return $this->instConfigs->getConfig($instSource);
    }

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     * @param array $params Parameters
     *
     * @return mixed Array of the patron's transactions
     */
    public function getMyTransactions($patron, $params = [])
    {
        /* @phpstan-ignore-next-line */
        $data = parent::getMyTransactions($patron, $params);
        if (isset($data['records'])) {
            $records = $data['records'];
            $records = $this->processOverdueTransactions($records);
            $data['records'] = $this->resolveIds($records, $patron);
            return $data;
        } else {
            return $this->processOverdueTransactions(
                $this->resolveIds($data, $patron)
            );
        }
    }

    /**
     * Return short loan requests for patron
     *
     * @param array $patron patron
     *
     * @return array
     */
    public function getMyShortLoans($patron)
    {
        /* @phpstan-ignore-next-line */
        $data = parent::getMyShortLoans($patron);
        if (isset($data['records'])) {
            $data['records'] = $this->resolveIds($data['records'], $patron);
            return $data;
        } else {
            return $this->resolveIds($data, $patron);
        }
    }

    /**
     * Get Patron Transaction History
     *
     * This is responsible for retrieving all historic transactions
     * (i.e. checked out items) by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     * @param array $params Retrieval params
     *
     * @return array        Array of the patron's transactions
     */
    public function getMyTransactionHistory($patron, $params)
    {
        $source = $this->getSource($patron['cat_username']);
        $driver = $this->getDriver($source);
        $supported = $this->driverSupportsMethod(
            $driver,
            'getMyTransactionHistory',
            compact('patron')
        );
        if (!$supported) {
            return ['success' => false, 'status' => 'driver_no_history'];
        }
        /* @phpstan-ignore-next-line */
        $data = parent::getMyTransactionHistory($patron, $params);
        if (isset($data['transactions'])) {
            $data['transactions'] = $this->resolveIds(
                $data['transactions'],
                $patron
            );
            return $data;
        } else {
            return $this->resolveIds($data, $patron);
        }
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed      Array of the patron's holds
     */
    public function getMyHolds($patron)
    {
        $holds = parent::getMyHolds($patron);
        $srrs = parent::getMyStorageRetrievalRequests($patron);
        $data = array_merge($holds, $srrs);
        return $this->resolveIds($data, $patron);
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $patron The patron array
     *
     * @return mixed Array of the patron's profile data
     */
    public function getMyProfile($patron)
    {
        $profile = parent::getMyProfile($patron);
        if (isset($profile['expiration_date'])) {
            $expired = $this->dateDiff($profile['expiration_date']);
            $profile['expired'] = ($expired > 0);
            $profile['will_expire'] = ($expired <= 0 && $expired >= -30);
        }
        return $profile;
    }

    /**
     * Helper method to determine whether or not a certain method can be
     * called on this driver.  Required method for any smart drivers.
     *
     * @param string $method The name of the called method.
     * @param array  $params Array of passed parameters.
     *
     * @return bool True if the method can be called with the given parameters,
     * false otherwise.
     */
    public function supportsMethod($method, $params)
    {
        if ($method == 'placeStorageRetrievalRequest') {
            return false;
        }
        if ($method == 'getDriverName' || $method == 'getIlsType') {
            return true;
        }
        return parent::supportsMethod($method, $params);
    }

    /**
     * Resolve identifiers
     *
     * @param array $data   Data from ILS driver
     * @param array $patron Patron data
     *
     * @return array Data with right identifiers
     */
    protected function resolveIds(array $data, array $patron): array
    {
        $source = $this->getSource($patron['cat_username']);
        $sourceConfig = $this->getDriverConfig($source);
        $config = $sourceConfig['IdResolver'] ?? [];
        $config['source'] = $source;
        return $this->idResolver->resolveIds($data, $config);
    }

    /**
     * Takes sigla and return library source for it
     *
     * @param string $sigla SIGLA library identifier
     *
     * @return string|null
     */
    public function siglaToSource($sigla): ?string
    {
        $siglaMapping = $this->config['SiglaMapping'] ?? [];
        $siglaMapping = is_array($siglaMapping) ? array_flip($siglaMapping) : [];
        return $siglaMapping[$sigla] ?? null;
    }

    /**
     * Library source to sigla
     *
     * @param string $source Library source identifier
     *
     * @return string|null
     */
    public function sourceToSigla(string $source): ?string
    {
        $siglaMapping = $this->config['SiglaMapping'] ?? [];
        return is_array($siglaMapping) ? ($siglaMapping[$source] ?? null) : null;
    }

    /**
     * Get Status By Item ID or Bibliographic ID
     *
     * This is responsible for retrieving the status information of a certain
     * record/item
     *
     * @param string|null $bibId  The record id to retrieve the holdings for
     * @param string|null $itemId The item id to retrieve the holdings for
     *
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     * @throws ILSException
     */
    public function getStatusByItemIdOrBibId(?string $bibId, ?string $itemId)
    {
        if ($bibId !== null && $itemId !== null) {
            $statuses = $this->getStatus($bibId);
            $itemId = $this->getLocalId($itemId);
            foreach ($statuses['holdings'] as $status) {
                if (($status['item_id'] ?? '') == $itemId) {
                    return $status;
                }
            }
        } elseif ($itemId !== null) {
            return $this->getStatusByItemId($itemId);
        }
        return [];
    }

    /**
     * Get Status By Item ID
     *
     * This is responsible for retrieving the status information of a certain
     * item.
     *
     * @param string $id The item id to retrieve the holdings for
     *
     * @throws ILSException
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatusByItemId($id)
    {
        $source = $this->getSource($id);
        $driver = $this->getDriver($source);
        if ($driver) {
            $status = $driver->getStatusByItemId($this->getLocalId($id));
            return $this->addIdPrefixes($status, $source);
        }
        return [];
    }

    /**
     * Get driver class name including namespace
     *
     * @param string $recordId Record identifier
     *
     * @throws \ReflectionException
     * @return string
     */
    public function getDriverName(string $recordId): string
    {
        $source = $this->getSource($recordId);
        return (!empty($source) && !empty($driver = $this->getDriver($source)))
            ? (new \ReflectionClass($driver))->getName()
            : '';
    }

    /**
     * Get ILS type - only suitable for XCNCIP2 driver
     *
     * @param string $recordId Record identifier
     *
     * @return string
     */
    public function getIlsType(string $recordId): string
    {
        $config = $this->getDriverConfig($this->getSource($recordId));
        return $config['Catalog']['ils_type'] ?? '';
    }

    /**
     * Set overdue status for expired transactions
     *
     * @param array $details details from ILS
     *
     * @return array
     */
    protected function processOverdueTransactions($details)
    {
        foreach ($details as &$detail) {
            if (
                isset($detail['duedate'])
                && $this->isExpired($detail['duedate'])
                && !isset($detail['dueStatus'])
            ) {
                $detail['dueStatus'] = 'overdue';
            }
        }
        return $details;
    }

    /**
     * Return if the date is in the past, used for checking expired checked
     * out items or registrations.
     *
     * @param string $expire Expiration date in display format
     *
     * @return bool is expired
     */
    protected function isExpired(string $expire): bool
    {
        return $this->dateDiff($expire) > 0;
    }

    /**
     * Return difference between base and target date in days
     *
     * @param string      $base   base date
     * @param string|null $target target date (if null use current date)
     *
     * @return int                 number of days
     *
     * @throws DateException
     */
    protected function dateDiff(string $base, string $target = null): int
    {
        $baseDate = $this->dateConverter->parseDisplayDate($base);
        if ($baseDate == false) {
            throw new DateException('Invalid date: ' . $base);
        }
        $targetDate = ($target == null) ? new \DateTime()
            : $this->dateConverter->parseDisplayDate($target);
        if ($targetDate == false) {
            throw new DateException('Invalid date: ' . $target);
        }
        $dateDiff = $baseDate->diff($targetDate);
        return ($dateDiff->invert == 0 ? 1 : -1) * $dateDiff->days;
    }
}
