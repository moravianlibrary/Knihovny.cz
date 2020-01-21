<?php
/**
 * VuFind Driver for Koha, using REST API
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
 * @package  KnihovnyCz\ILS\Driver
 * @author   Bohdan Inhliziian <bohdan.inhliziian@gmail.com.cz>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\ILS\Driver;

use VuFind\ILS\Driver\AbstractBase;
use VuFind\Date\DateException;
use VuFind\Exception\ILS as ILSException;

/**
 * VuFind Driver for Koha, using REST API
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Driver
 * @author   Bohdan Inhliziian <bohdan.inhliziian@gmail.com>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 * @link     https://knihovny.cz Main Page
 */
class KohaRest extends AbstractBase implements \Zend\Log\LoggerAwareInterface,
    \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;
    use \VuFind\Log\LoggerAwareTrait;
    use \VuFind\ILS\Driver\CacheTrait;

    /**
     * Library prefix
     *
     * @var string
     */
    protected $source = '';

    /**
     * Date converter object
     *
     * @var \VuFind\Date\Converter
     */
    protected $dateConverter = null;

    protected $defaultPickUpLocation;

    protected $kohaRestService;

    /**
     * @var array|null cached libraries
     */
    protected static $libraries;

    /**
     * Mappings from renewal block reasons
     *
     * @var array
     */
    protected $renewalBlockMappings = [
        'no_item' => 'cannot_renew_no_item',
        'no_checkout' => 'cannot_renew_no_checkout',
        'item_denied_renewal' => 'cannot_renew_item_denied_renewal',
        'too_soon' => 'cannot_renew_yet',
        'auto_too_soon' => 'cannot_renew_auto_too_soon',
        'onsite_checkout' => 'cannot_renew_onsite',
        'auto_renew' => 'cannot_renew_auto_renew',
        'on_reserve' => 'cannot_renew_item_requested',
        'too_many' => 'cannot_renew_too_many',
        'auto_too_late' => 'cannot_renew_auto_too_late',
        'auto_too_much_oweing' => 'cannot_renew_auto_too_much_oweing',
        'restriction' => 'cannot_renew_user_restricted',
        'overdue' => 'cannot_renew_item_overdue',
    ];

    /**
     * Fines and charges mappings
     *
     * @var array
     */
    protected $finesMappings = [
        "L" => "Book Replacement Charge",
        "N" => "Card Replacement Charge",
        "OVERDUE" => "Reminder Charge",
        "A" => "Renewal Fee",
        "Res" => "Reservation Charge",
        "Rent" => "Rental",
        "M" => "Other",
    ];

    /**
     * Checkout statuses
     *
     * @var array
     */
    protected $statuses = [
        'checked_out' => 'On Loan',
        'on_shelf' => 'Available On Shelf',
        'in_transfer' => 'In Transit Between Library Locations',
        'waiting_hold' => 'Available For Pickup',
    ];

    /**
     * Constructor
     *
     * @param \VuFind\Date\Converter $dateConverter   Date converter
     * @param KohaRest\Service       $kohaRestService Koha API authentication service
     */
    public function __construct(\VuFind\Date\Converter $dateConverter,
        KohaRest\Service $kohaRestService
    ) {
        $this->dateConverter = $dateConverter;
        $this->kohaRestService = $kohaRestService;
    }

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
        // Validate config
        $required = ['host', 'tokenEndpoint', 'clientId', 'clientSecret'];
        foreach ($required as $current) {
            if (!isset($this->config['Catalog'][$current])) {
                throw new ILSException("Missing Catalog/{$current} config setting.");
            }
        }

        $this->defaultPickUpLocation =
            $this->config['Holds']['defaultPickUpLocation'] ?? '';

        if ($this->defaultPickUpLocation === 'user-selected') {
            $this->defaultPickUpLocation = false;
        }

        $this->source = $this->config['Availability']['source'] ?? null;

        $this->kohaRestService->setConfig($this->config);
        $this->kohaRestService->setSource($this->source);
    }

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @return array    On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber,
     * use_unknown_message, services, error.
     */
    public function getStatus($id)
    {
        return $this->getItemStatusesForBiblio($id);
    }

    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     *
     * @param array $ids The array of record ids to retrieve the status for
     *
     * @return mixed     An array of getStatus() return values on success.
     */
    public function getStatuses($ids)
    {
        return array_map(
            function ($id) {
                return $this->getItemStatusesForBiblio($id);
            }, $ids
        );
    }

    /**
     * Get Holding
     *
     * This is responsible for retrieving the holding information of a certain
     * record.
     *
     * @param string $id      The record id to retrieve the holdings for
     * @param array  $patron  Patron data
     * @param array  $options Additional options - optional 'page', 'itemLimit' and
     *                        'offset' parameters used for result pagination).
     *
     * @return array  On success an array with the key "total" containing the total
     * number of items for the given bib id, and the key "holdings" containing an
     * array of holding information each one with these keys: id, source,
     * availability, status, location, reserve, callnumber, duedate, returnDate,
     * number, barcode, item_notes, item_id, holding_id, addLink, description
     *
     * @throws ILSException
     */
    public function getHolding($id, ?array $patron = null, array $options = [])
    {
        //FIXME return value should be as described in doc comment
        //FIXME implement pagination (pass $options param)
        return $this->getItemStatusesForBiblio($id, $patron);
    }

    /**
     * Get Purchase History
     *
     * This is responsible for retrieving the acquisitions history data for the
     * specific record (usually recently received issues of a serial).
     *
     * @param string $id The record id to retrieve the info for
     *
     * @return array     An array with the acquisitions data on success.
     * @throws ILSException
     */
    public function getPurchaseHistory($id)
    {
        return [];
    }

    /**
     * Check whether the patron is blocked from placing requests (holds/ILL/SRR).
     *
     * @param array $patron Patron data from patronLogin().
     *
     * @return mixed A boolean false if no blocks are in place and an array
     * of block reasons if blocks are in place
     */
    public function getRequestBlocks($patron) //TODO deal if it is needed
    {
        return $this->getAccountBlocks($patron);
    }

    /**
     * Check whether the patron has any blocks on their account.
     *
     * @param array $patron Patron data from patronLogin().
     *
     * @return mixed A boolean false if no blocks are in place and an array
     * of block reasons if blocks are in place
     */
    public function getAccountBlocks($patron)
    {
        $result = $this->makeRequest(['v1', 'patrons', $patron['id']]);
        if ($result['data']['restricted']) {
            return ['patron_account_restricted'];
        }
        return false;
    }

    /**
     * Get Renew Details
     *
     * @param array $checkOutDetails An array of item data
     *
     * @return string Data for use in a form field
     */
    public function getRenewDetails($checkOutDetails)
    {
        return $checkOutDetails['checkout_id'] . '|' . $checkOutDetails['item_id'];
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $patron The patron array
     *
     * @throws ILSException
     * @return array        Array of the patron's profile data on success.
     */
    public function getMyProfile($patron)
    {
        $result = $this->makeRequest(['v1', 'patrons', $patron['id']]);
        $result = $result['data'];
        $expiryDate = isset($result['expiry_date'])
            ? $this->normalizeDate($result['expiry_date'])  : '';
        return [
            //'cat_username' => $patron['cat_username'], //FIXME: is this needed?
            'id' => $patron['id'],
            'firstname' => $result['firstname'],
            'lastname' => $result['surname'],
            'address1' => $result['address'],
            'address2' => $result['address2'],
            'city' => $result['city'],
            'country' => $result['country'],
            'zip' => $result['postal_code'],
            'phone' => $result['phone'],
            'group' => $result['category_id'], //FIXME: maybe better as category name - need future api enhancements
            //'blocks' => $result['restricted'], //FIXME: is this needed?
            //'email' => $result['email'], //FIXME: is this needed?
            'expiration_date' => $expiryDate,
        ];
    }

    protected function getCheckouts( array $patron, array $params, bool $history = false)
    {
        $queryParams = [
            'patron_id' => $patron['id'],
            'checked_in' => $history,
            '_page' => $params['page'] ?? 1,
            '_per_page' => $params['limit'] ?? 20,
            '_match' => 'exact',
            '_order_by' => $params['sort'] ?? '-checkout_date',
        ];

        $transactions = $this->makeRequest(['v1', 'checkouts'], $queryParams);

        $totalCountHeader = $transactions['headers']->get('x-total-count');
        $totalCount = (int)$totalCountHeader->getFieldValue() ?? 0;
        $result = [
            'count' => $totalCount,
            'transactions' => [],
        ];

        foreach ($transactions['data'] as $entry) {
            if (isset($entry['item_id'])) {
                try {
                    $item = $this->getItem($entry['item_id']);
                } catch (\Exception $e) {
                    $item = [];
                }
               if (isset($item['biblio_id'])) {
                    $biblio = $this->getBiblio($item['biblio_id']);
                }
            }

            $transaction = [
                'id' => $item['biblio_id'] ?? null,
                'checkout_id' => $entry['checkout_id'],
                'item_id' => $entry['item_id'],
                'barcode' => $item['barcode'] ?? null,
                'title' => $biblio['title'] ?? '',
                'volume' => $item['serial_enum_chron'] ?? '',
                'checkoutDate' => $this->normalizeDate($entry['checkout_date']),
                'dueDate' => $this->normalizeDate($entry['due_date']),
                //'duedate' => $this->normalizeDate($entry['due_date']), //FIXME is this variant needed?
                'dueStatus' => $this->determineDueStatus($entry['due_date']),
                'returnDate' => $this->normalizeDate($entry['checkin_date']),
                'renew' => $entry['renewals'],
                'publication_year' => $biblio['copyright_date'] ?? $biblio['publication_year'] ?? '',
                'borrowingLocation' => $this->getLibraryName($entry['library_id']),
            ];

            if(!empty($entry['checkin_date']) && !empty($item)) {
                $renewability = $this->getCheckoutRenewability($entry['checkout_id']);
                $holds = $this->makeRequest(['v1', 'contrib', 'bibliocommons', 'biblios', $item['biblio_id'], 'holds']);
                $transaction['renewable'] = $renewability['allows_renewal'] ?? false;
                $transaction['renewLimit'] = $renewability['max_renewals'] ?? null;
                $transaction['message'] =
                    $this->renewalBlockMappings[$renewability['error']]
                    ?? $renewability['error'] ?? null;
                $transaction['request'] = $holds['status'] == 200 ? count($holds['data']) : 0;
            }

            $result['transactions'][] = $transaction;
        }
        return $result;
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
     * @throws ILSException
     * @return array        Array of the patron's transactions on success.
     */
    public function getMyTransactions($patron, $params)
    {
        $checkouts = $this->getCheckouts($patron, $params, false);
        return [
            'count' => $checkouts['count'],
            'records' => $checkouts['transactions'],
        ];
    }

    /**
     * Get Patron Loan History
     *
     * This is responsible for retrieving all historic loans (i.e. items previously
     * checked out and then returned), for a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     * @param array $params Parameters
     *
     * @throws DateException
     * @throws ILSException
     * @return array        Array of the patron's transactions on success.
     */
    public function getMyTransactionHistory($patron, $params)
    {
        return $this->getCheckouts($patron, $params, true);
    }

    /**
     * Checks if item is renewable
     *
     * @param integer $checkoutId Checkout identifier
     *
     * @return array
     * @throws ILSException
     */
    public function getCheckoutRenewability($checkoutId)
    {
        $result = $this->makeRequest(
            ['v1', 'checkouts', $checkoutId, 'allows_renewal']
        );
        return $result['data'];
    }

    /**
     * Renew My Items
     *
     * Function for attempting to renew a patron's items.  The data in
     * $renewDetails['details'] is determined by getRenewDetails().
     *
     * @param array $renewDetails An array of data required for renewing items
     *                            including the Patron ID and an array of renewal IDS
     *
     * @return array              An array of renewal information keyed by item ID
     * @throws ILSException
     */
    public function renewMyItems($renewDetails)
    {
        $finalResult = ['details' => []];

        foreach ($renewDetails['details'] as $details) {
            list($checkoutId, $itemId) = explode('|', $details);
            $result = $this->makeRequest(
                ['v1', 'checkouts', $checkoutId, 'renewal'], false, 'POST'
            );
            if ($result['code'] == 403) {
                $finalResult['details'][$itemId] = [
                    'item_id' => $itemId,
                    'success' => false
                ];
            } else {
                $finalResult['details'][$itemId] = [
                    'item_id' => $itemId,
                    'success' => true,
                    'new_date' => !empty($result['data']['due_date'])
                        ? $this->normalizeDate($result['data']['due_date'])
                        : '',
                ];
            }
        }
        return $finalResult;
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @throws ILSException
     * @return array        Array of the patron's holds on success.
     */
    public function getMyHolds($patron)
    {
        $result = $this->makeRequest(
            ['v1', 'holds'],
            ['patron_id' => $patron['id'], '_match' => 'exact', ]
        );

        $holds = [];
        foreach ($result['data'] as $entry) {
            $biblio = $this->getBiblio($entry['biblio_id']);
            $holds[] = [
                'id' => $entry['biblio_id'],
                'item_id' => $entry['item_id'] ?? null,
                'location' => $this->getLibraryName(
                    $entry['pickup_library_id'] ?? null
                ),
                'create' => !empty($entry['hold_date'])
                    ? $this->normalizeDate($entry['hold_date']) : '',
                'expire' => !empty($entry['expiration_date'])
                    ? $this->normalizeDate($entry['expiration_date']) : '',
                'position' => $entry['priority'],
                'available' => !empty($entry['waiting_date']),
                'hold_id' => $entry['hold_id'],
                'in_transit' => !empty($entry['status']) && $entry['status'] == 'T',
                'volume' => $biblio['part_number'] ?? '',
                'publication_year' => $biblio['copyright_date'] ?? $biblio["publication_year"] ?? '',
                'title' => $biblio['title'] ?? '',
                'isbn' => $biblio['isbn'] ?? '',
                'issn' => $biblio['issn'] ?? '',
            ];
        }
        return $holds;
    }

    /**
     * Cancel Holds
     *
     * Attempts to Cancel a hold. The data in $cancelDetails['details'] is determined
     * by getCancelHoldDetails().
     *
     * @param array $cancelDetails An array of item and patron data
     *
     * @return array               An array of data on each request including
     * whether or not it was successful and a system message (if available)
     * @throws ILSException
     */
    public function cancelHolds($cancelDetails)
    {
        $details = $cancelDetails['details'];
        $count = 0;
        $response = [];

        foreach ($details as $detail) {
            list($holdId, $itemId) = explode('|', $detail, 2);
            $result = $this->makeRequest(
                ['v1', 'holds', $holdId], false, 'DELETE'
            );

            if ($result['code'] != 200) {
                $response[$itemId] = [
                    'success' => false,
                    'status' => 'hold_cancel_fail',
                    'sysMessage' => false
                ];
            } else {
                $response[$itemId] = [
                    'success' => true,
                    'status' => 'hold_cancel_success'
                ];
                ++$count;
            }
        }
        return ['count' => $count, 'items' => $response];
    }

    /**
     * Get Pick Up Locations
     *
     * This is responsible for gettting a list of valid library locations for
     * holds / recall retrieval
     *
     * @param array|false $patron      Patron information returned by the patronLogin
     * method.
     * @param array|null  $holdDetails Optional array, only passed in when getting a list
     *                                 in the context of placing a hold; contains most of
     *                                 the same values passed to placeHold, minus the
     *                                 patron data.  May be used to limit the pickup
     *                                 options or may be ignored.  The driver must not
     *                                 add new options to the return array based on this
     *                                 data or other areas of VuFind may behave
     *                                 incorrectly.
     *
     * @throws ILSException
     * @return array        An array of associative arrays with locationID and
     * locationDisplay keys
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPickUpLocations($patron = false, $holdDetails = null)
    {
        if (!isset(self::$libraries)) {
            $this->requestLibraries();
        }
        $result = self::$libraries;

        $locations = [];
        $excluded = isset($this->config['Holds']['excludePickupLocations'])
            ? explode(':', $this->config['Holds']['excludePickupLocations']) : [];
        foreach ($result as $location) {
            if (!$location['pickup_location']
                || in_array($location['library_id'], $excluded)
            ) {
                continue;
            }
            $locations[] = [
                'locationID' => $location['library_id'],
                'locationDisplay' => $location['name']
            ];
        }
        return $locations;
    }

    /**
     * Get Default Pick Up Location
     *
     * Returns the default pick up location
     *
     * @param array|false $patron      Patron information returned by the patronLogin
     * method.
     * @param array|null  $holdDetails Optional array, only passed in when getting a list
     *                                 in the context of placing a hold; contains most of
     *                                 the same values passed to placeHold, minus the
     *                                 patron data.  May be used to limit the pickup
     *                                 options or may be ignored.
     *
     * @return false|string      The default pickup location for the patron or false
     * if the user has to choose.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultPickUpLocation($patron = false, $holdDetails = null)
    {
        return $this->defaultPickUpLocation;
    }

    /**
     * Get Cancel Hold Details
     *
     * Get required data for canceling a hold. This value is used by relayed to the
     * cancelHolds function when the user attempts to cancel a hold.
     *
     * @param array $holdDetails An array of hold data
     *
     * @return string Data for use in a form field
     */
    public function getCancelHoldDetails($holdDetails)
    {
        return $holdDetails['available'] || $holdDetails['in_transit'] ? ''
            : $holdDetails['hold_id'] . '|' . $holdDetails['item_id'];
    }

    /**
     * Check if request is valid
     *
     * This is responsible for determining if an item is requestable
     *
     * @param string $id     The Bib ID
     * @param array  $data   An Array of item data
     * @param array  $patron An array of patron data
     *
     * @return mixed An array of data on the request including
     * whether or not it is valid and a status message. Alternatively a boolean
     * true if request is valid, false if not.
     * @throws ILSException
     */
    public function checkRequestIsValid($id, $data, $patron)
    {
        if ($this->getAccountBlocks($patron) !== false) {
            return false;
        }
        $level = $data['level'] ?? 'copy';
        if ('title' == $level) {
            $result = $this->makeRequest(
                ['v1', 'contrib', 'knihovny_cz', 'biblios', $id, 'alows_hold'],
                [
                    'patron_id' => $patron['id'],
                    'library_id' => $this->getDefaultPickUpLocation($patron)
                ]
            );
            $result = $result['data'];
            if (!empty($result['allows_hold']) && $result['allows_hold'] == true) {
                return [
                    'valid' => true,
                    'status' => 'title_hold_place'
                ];
            }
            return [
                'valid' => false,
                'status' => 'hold_error_blocked' //FIXME translation
            ];
        }
        $result = $this->makeRequest(
            [
                'v1', 'contrib', 'knihovny_cz', 'items', $data['item_id'],
                'allows_hold'
            ], [
               'patron_id' => $patron['id'],
                'library_id' => $this->getDefaultPickUpLocation($patron)
            ]
        );
        $result = $result['data'];
        if (!empty($result['allows_hold']) && $result['allows_hold'] == true) {
            return [
                'valid' => true,
                'status' => 'hold_place'
            ];
        }
        return [
            'valid' => false,
            'status' => 'hold_error_blocked' //FIXME translation
        ];
    }

    /**
     * Place Hold
     *
     * Attempts to place a hold or recall on a particular item and returns
     * an array with result details or throws an exception on failure of support
     * classes
     *
     * @param array $holdDetails An array of item and patron data
     *
     * @return mixed An array of data on the request including
     * whether or not it was successful and a system message (if available)
     * @throws DateException
     * @throws ILSException
     */
    public function placeHold($holdDetails)
    {
        $patron = $holdDetails['patron'];
        $level = isset($holdDetails['level']) && !empty($holdDetails['level'])
            ? $holdDetails['level'] : 'copy';
        $pickUpLocation = !empty($holdDetails['pickUpLocation'])
            ? $holdDetails['pickUpLocation'] : $this->defaultPickUpLocation;
        $itemId = isset($holdDetails['item_id']) ? $holdDetails['item_id'] : false;
        $comment = isset($holdDetails['comment']) ? $holdDetails['comment'] : '';
        $bibId = $holdDetails['id'];

        // Convert last interest date from Display Format to Koha's required format
        try {
            $lastInterestDate = $this->dateConverter->convertFromDisplayDate(
                'Y-m-d', $holdDetails['requiredBy']
            );
        } catch (DateException $e) {
            // Hold Date is invalid
            return $this->holdError('hold_date_invalid');
        }

        if ($level == 'copy' && empty($itemId)) {
            throw new ILSException("Hold level is 'copy', but item ID is empty");
        }

        try {
            $checkTime = $this->dateConverter->convertFromDisplayDate(
                'U', $holdDetails['requiredBy']
            );
            if (!is_numeric($checkTime)) {
                throw new DateException('Result should be numeric');
            }
        } catch (DateException $e) {
            throw new ILSException('Problem parsing required by date.');
        }

        if (time() > $checkTime) {
            // Hold Date is in the past
            return $this->holdError('hold_date_past');
        }

        // Make sure pickup location is valid
        if (!$this->pickUpLocationIsValid($pickUpLocation, $patron, $holdDetails)) {
            return $this->holdError('hold_invalid_pickup');
        }

        $request = [
            'biblio_id' => (int)$bibId,
            'patron_id' => (int)$patron['id'],
            'pickup_library_id' => $pickUpLocation,
            'notes' => $comment,
            'expiration_date' => $lastInterestDate,
        ];
        if ($level == 'copy') {
            $request['item_id'] = (int)$itemId;
        }

        $result = $this->makeRequest(
            ['v1', 'holds'], json_encode($request), 'POST'
        );

        if ($result['code'] >= 300) {
            return $this->holdError($result['data']['error']);
        }
        return ['success' => true];
    }

    /**
     * Get Patron Fines
     *
     * This is responsible for retrieving all fines by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @throws ILSException
     * @return array        Array of the patron's fines on success.
     */
    public function getMyFines($patron)
    {
        $result = $this->makeRequest(
            ['v1', 'patrons', $patron['id'], 'account'], false, 'GET'
        );

        $fines = [];

        foreach ($result['data']['outstanding_debits']['lines'] as $entry) {
            $fineDescription = (isset($this->finesMappings[$entry['account_type']]))
                ? $this->translate($this->finesMappings[$entry['account_type']])
                : $entry['description'];
            $fines[] = [
                'amount' => $entry['amount'] * 100,
                'checkout' => $this->normalizeDate($entry['date']),
                'fine' =>  $fineDescription,
                'title' => $entry['description'],
                'balance' => $entry['amount_outstanding'] * 100,
                'createdate' => $entry['date'],
                'duedate' => '',
                'item_id' => $entry['item_id'],
            ];
        }
        return $fines;
    }

    /**
     * Make Request
     *
     * Makes a request to the Koha REST API
     *
     * @param array      $hierarchy Array of values to embed in the URL path of
     *                              the request
     * @param array|bool|string  $params  A keyed array of query data
     * @param string             $method  The http request method to use (Default is GET)
     * @param array              $headers Request headers, an array, where key is
     *                                    header name and value is header value
     *
     * @return   mixed
     * @throws   ILSException *@throws \Exception
     * @internal param bool $authNeeded
     */
    protected function makeRequest($hierarchy, $params = false, $method = 'GET', $headers = [])
    {
        // Set up the request
        $apiUrl = $this->config['Catalog']['host'];

        $hierarchy = array_map('urlencode', $hierarchy);
        $apiUrl .= '/' . implode('/', $hierarchy);

        $client = $this->kohaRestService->createClient($apiUrl);

        // Add params
        if (false !== $params) {
            if ('GET' === $method || 'DELETE' === $method) {
                    $client->setParameterGet($params);
            } else {
                $body = '';
                if (is_string($params)) {
                    $body = $params;
                } else {
                    if (isset($params['__body__'])) {
                        $body = $params['__body__'];
                        unset($params['__body__']);
                        $client->setParameterGet($params);
                    } else {
                        $client->setParameterPost($params);
                    }
                }
                if ('' !== $body) {
                    $client->getRequest()->setContent($body);
                    $client->getRequest()->getHeaders()->addHeaderLine(
                        'Content-Type', 'application/json'
                    );
                }
            }
        }

        if (!empty($headers)) {
            $requestHeaders = $client->getRequest()->getHeaders();
            foreach ($headers as $name => $value) {
                $requestHeaders->addHeaderLine($name, [$value]);
            }
        }

        // Send request and retrieve response
        $startTime = microtime(true);
        $client->setMethod($method);

        try {
            $response = $client->send();
        } catch (\Exception $e) {
            $this->logError(
                "$method request for '$apiUrl' failed: " . $e->getMessage()
            );
            throw new ILSException('Problem with Koha REST API.');
        }

        // If we get a 401, we need to renew the access token and try again
        if ($response->getStatusCode() == 401) {
            $this->kohaRestService->invalidateToken();
            $client = $this->kohaRestService->createClient($apiUrl);

            try {
                $response = $client->send();
            } catch (\Exception $e) {
                $this->logError(
                    "$method request for '$apiUrl' failed: " . $e->getMessage()
                );
                throw new ILSException('Problem with Koha REST API.');
            }
        }

        $result = $response->getBody();

        $fullUrl = $apiUrl;
        if ($method == 'GET') {
            $fullUrl .= '?' . $client->getRequest()->getQuery()->toString();
        }
        $this->debug(
            '[' . round(microtime(true) - $startTime, 4) . 's]'
            . " $method request $fullUrl" . PHP_EOL . 'response: ' . PHP_EOL
            . $result
        );

        // Handle errors as complete failures only if the API call didn't return
        // valid JSON that the caller can handle
        $decodedResult = json_decode($result, true);
        if (!$response->isSuccess()
            && (null === $decodedResult || !empty($decodedResult['error']))
        ) {
            $params = $method == 'GET'
                ? $client->getRequest()->getQuery()->toString()
                : $client->getRequest()->getPost()->toString();
            $this->logError(
                "$method request for '$apiUrl' with params '$params' and contents '"
                . $client->getRequest()->getContent() . "' failed: "
                . $response->getStatusCode() . ': ' . $response->getReasonPhrase()
                . ', response content: ' . $response->getBody()
            );
            throw new ILSException('Problem with Koha REST API.');
        }

        $result = [
            'data' => $decodedResult,
            'code' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
        ];

        return $result;
    }

    /**
     * Get Item Statuses
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id     The record id to retrieve the holdings for
     * @param array  $patron Patron information, if available
     *
     * @return array An associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     * @throws ILSException
     */
    protected function getItemStatusesForBiblio($id, $patron = null)
    {
        $result = [];
        $availability = $this->makeRequest(
            ['v1', 'contrib', 'knihovny_cz', 'biblios', $id, 'allows_checkout']
        );
        $availability = $availability['data'];

        $holdable = 'Y';
        if ($patron) {
            $holdability = $this->makeRequest(
                ['v1', 'contrib', 'knihovny_cz', 'biblios', $id, 'allows_hold'],
                [
                    'patron_id' => $patron['id'],
                    'library_id' => $this->getDefaultPickUpLocation($patron)
                ]
            );
            if ($holdability['code'] == '200' && $holdability['data']['allows_hold'] == false) {
                $holdable = 'N';
            }
        }

        $items = $this->makeRequest(['v1', 'contrib', 'bibliocommons', 'biblios', $id, 'items']);
        $items = $items['data'];

        $holds = $this->makeRequest(['v1', 'holds'], ['biblio_id' => $id]);
        $holds = $holds['data'];

        foreach ($items as $item) {
            if ($item) {
                $status = 'Available On Shelf';
                $label = 'label-success';
                $duedate = null;
                $available = true;
                if (isset($availability[$item['item_id']])) {
                    $status = $this->statuses[
                        $availability[$item['item_id']]['allows_checkout_status']
                    ];
                    $status = $item['notforloan'] ? 'Not For Loan' : $status;
                    $label = $availability[$item['item_id']]['allows_checkout']
                        ? 'label-success' : 'label-warning';
                    $available = $availability[$item['item_id']]['allows_checkout'];
                    $duedate = isset($availability[$item['item_id']]['date_due'])
                        ? $this->normalizeDate(
                            $availability[$item['item_id']]['date_due']
                        ) : null;
                }
                $entry = [
                    'id' => $id,
                    'item_id' => $item['item_id'],
                    'department' => $this->getItemLocationName($item),
                    'location' => $item['location'],
                    'availability' => $item['notforloan'] ? false : $available,
                    'status' => $status,
                    'reserve' => count($holds) >= 1 ? 'Y' : 'N',
                    'callnumber' => $item['callnumber'],
                    'duedate' => $duedate,
                    'number' => $item['serial_enum_chron'],
                    'barcode' => $item['barcode'], 'label' => $label,
                    'supplements' => $item['materials_notes'],
                ];
                if (!empty($item['public_notes'])) {
                    $entry['item_notes'] = [$item['public_notes']];
                }

                if ($holdable == 'Y') {
                    $entry['is_holdable'] = true;
                    $entry['level'] = 'copy';
                    $entry['addLink'] = 'check';
                } else {
                    $entry['is_holdable'] = false;
                }
                $result[] = $entry;
            }
        }
        return $result;
    }

    /**
     * Fetch an item record from Koha
     *
     * @param int $id Item id
     *
     * @return array|null
     * @throws ILSException
     */
    protected function getItem($id)
    {
        static $cachedRecords = [];
        if (!isset($cachedRecords[$id])) {
            $result = $this->makeRequest(
                ['v1', 'contrib', 'bibliocommons', 'items', $id]
            );
            $cachedRecords[$id] = $result['data'];
        }
        return $cachedRecords[$id];
    }

    /**
     * Fetch a bib record from Koha
     *
     * @param int $id Bib record id
     *
     * @return array|null
     * @throws ILSException
     */
    protected function getBiblio($id)
    {
        static $cachedRecords = [];
        if (!isset($cachedRecords[$id])) {
            $result = $this->makeRequest(
                ['v1', 'biblios', $id],
                [],
                'GET',
                ['Accept' => 'application/json']
            );
            $cachedRecords[$id] = ($result['code'] == 200) ? $result['data'] : [];
        }
        return $cachedRecords[$id];
    }

    /**
     * Is the selected pickup location valid for the hold?
     *
     * @param string $pickUpLocation Selected pickup location
     * @param array  $patron         Patron information returned by the patronLogin
     *                               method.
     * @param array  $holdDetails    Details of hold being placed
     *
     * @return bool
     * @throws ILSException
     */
    protected function pickUpLocationIsValid($pickUpLocation, $patron, $holdDetails)
    {
        $pickUpLibs = $this->getPickUpLocations($patron, $holdDetails);
        foreach ($pickUpLibs as $location) {
            if ($location['locationID'] == $pickUpLocation) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return a hold error message
     *
     * @param string $message error message
     *
     * @return array
     */
    protected function holdError($message)
    {
        return [
            'success' => false,
            'sysMessage' => $message
        ];
    }

    protected function requestLibraries()
    {
        $result = $this->makeRequest(['v1', 'libraries']);
        foreach ($result['data'] as $library) {
            self::$libraries[$library['library_id']] = $library;
        }
    }

    /**
     * Get library by id
     *
     * @param string $libraryId Library identifier (code)
     *
     * @return array|null library data
     */
    protected function getLibrary(string $libraryId)
    {
        if (!isset(self::$libraries)) {
            $this->requestLibraries();
        }
        return self::$libraries[$libraryId] ?? null;
    }

    protected function getLibraryName(string $libraryId)
    {
        if (!isset(self::$libraries)) {
            $this->requestLibraries();
        }
        if (isset(self::$libraries[$libraryId])) {
            return self::$libraries[$libraryId]['name'];
        }
        return $libraryId;
    }

    /**
     * Return a location for a Koha item
     *
     * @param array $item Item
     *
     * @return string
     * @throws ILSException
     */
    protected function getItemLocationName($item)
    {
        $library_id = $item['holding_library'] ?? $item['home_library'];
        $name = $this->translate("location_$library_id");
        if ($name === "location_$library_id") {
            $library = $this->getLibrary($library_id);
            if ($library) {
                $name = $library['name'];
            }
        }
        return $name;
    }

    public function getConfig($func)
    {
        $config = [];
        switch ($func) {
        case 'Holds':
                $config = [
                    "HMACKeys" => "id:item_id",
                    "extraHoldFields" => "comments:requiredByDate:pickUpLocation",
                    "defaultRequiredDate" => "0:0:1",
                ];
            break;
        case 'IllRequests':
            $config = [ "HMACKeys" => "id:item_id" ];
            break;
        case 'getMyTransactionHistory':
        case 'getMyTransactions':
            $config = [
                'max_results' => '200',
                'default_page_size' => '20',
                'sort' => [
                    '-checkout_date' => 'sort_checkout_date_desc',
                    '+checkout_date' => 'sort_checkout_date_asc',
                    '-checkin_date' => 'sort_return_date_desc',
                    '+checkin_date' => 'sort_return_date_asc',
                ],
                'default_sort' => '-checkout_date',
            ];
            break;
        }
        return $config;
    }

    protected function normalizeDate($date, $withTime = false)
    {
        $createFormat = $withTime ? 'c': 'Y-m-d';
        return $this->dateConverter->convertToDisplayDate($createFormat, $date);
    }

    protected function determineDueStatus($dueDate)
    {
        $dueStatus = false;
        $now = time();
        $dueTimeStamp = strtotime($dueDate);
        if (is_numeric($dueTimeStamp)) {
            if ($now > $dueTimeStamp) {
                $dueStatus = 'overdue';
            } elseif ($now > $dueTimeStamp - (1 * 24 * 60 * 60)) {
                $dueStatus = 'due';
            }
        }
        return $dueStatus;
    }
}