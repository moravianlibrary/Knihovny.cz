<?php

namespace KnihovnyCz\ILS\Driver;

use VuFind\Date\DateException;
use VuFind\Exception\ILS as ILSException;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\ILS\Driver\Aleph as AlephBase;

/**
 * Class Aleph
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Driver
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Aleph extends AlephBase implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    protected const MAX_LOANS = 989;

    public const DATE_FORMATS = [
        '/^[0-9]{8}$/' => 'Ynd',
        '/^[0-9]+\/[A-Za-z]{3}\/[0-9]{4}$/' => 'd/M/Y',
        '/^[0-9]+\/[A-Za-z]{3}\/[0-9]{2}$/' => 'd/M/y',
        '/^[0-9]+\/[0-9]+\/[0-9]{4}$/' => 'd/m/Y',
        '/^[0-9]+\/[0-9]+\/[0-9]{2}$/' => 'd/m/y',
        '/^[0-9]+\. [0-9]+\. [0-9]{4}$/' => 'd. m. Y',
    ];

    protected const TIME_FORMAT = '/^[0-2][0-9][0-6][0-9]$/';

    protected const ILL_BLANK_FORM_LABEL_PREFIX = 'ill_blank_form_';

    protected bool $showAlephLabelBlocks = false;

    protected string $source = '';

    /**
     * Public Function which retrieves historic loan, renew, hold and cancel
     * settings from the driver ini file.
     *
     * @param string $func   The name of the feature to be checked
     * @param array  $params Optional feature-specific parameters (array)
     *
     * @return array An array with key-value pairs.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConfig($func, $params = [])
    {
        if ($func == 'getMyShortLoans') {
            return [];
        } elseif ($func == 'getMyPaymentLink') {
            return [];
        } elseif ($func == 'Holdings') {
            return [ 'filters' => [ 'year', 'volume' ] ];
        }
        return parent::getConfig($func, $params);
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
        parent::init();
        $this->showAlephLabelBlocks = $this->config['ProfileBlocks']['showAlephLabel'] ?? false;
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
        // Short loans are only available if properly configured
        if ($method == 'getMyShortLoans') {
            return !empty($this->config['ShortLoan']['enabled']);
        }
        // Short loans are only available if properly configured
        if ($method == 'getMyPaymentLink') {
            return !empty($this->config['Payment']['url']);
        }
        if ($method == 'getMyProlongRegistrationLink') {
            return !empty($this->config['ProlongRegistration']['url']);
        }
        if ($method == 'getMyILLRequests') {
            return !empty($this->config['ILLRequests']['enabled']);
        }
        if ($method == 'getBlankIllRequestTypes') {
            return !empty($this->config['ILLRequests']['placingEnabled']);
        }
        if ($method == 'getMyBlocks') {
            return !empty($this->config['ProfileBlocks']['enabled']);
        }
        return parent::supportsMethod($method, $params);
    }

    /**
     * Get Holding
     *
     * This is responsible for retrieving the holding information of a certain
     * record.
     *
     * @param string $id      The record id to retrieve the holdings for
     * @param array  $patron  Patron data
     * @param array  $options Extra options (not currently used)
     *
     * @return array         On success, an associative array with the following
     * keys: id, availability (boolean), status, location, reserve, callnumber,
     * duedate, number, barcode.
     */
    public function getHolding($id, array $patron = null, array $options = [])
    {
        $holding = [];
        [$bib, $sys_no] = $this->parseId($id);
        $resource = $bib . $sys_no;
        $params = ['view' => 'full'];
        if (!empty($patron['id'])) {
            $params['patron'] = $patron['id'];
        } elseif (isset($this->defaultPatronId)) {
            $params['patron'] = $this->defaultPatronId;
        }
        $filters = [];
        if (isset($options['volume'])) {
            $filters['volume'] = $options['volume'];
        }
        if (isset($options['year'])) {
            $filters['year'] = $options['year'];
        }
        $xml = $this->doRestDLFRequest(['record', $resource, 'items'], $params + $filters);
        if (!empty($xml->{'items'})) {
            $items = $xml->{'items'}->{'item'};
        } else {
            $items = [];
        }
        foreach ($items as $item) {
            $item_status_code = (string)$item->{'z30-item-status-code'}; // $isc
            // $ipsc:
            $item_process_status = (string)$item->{'z30-item-process-status-code'};
            $sub_library_code = (string)$item->{'z30-sub-library-code'}; // $slc
            $z30 = $item->z30;
            /* @phpstan-ignore-next-line */
            if ($this->alephTranslator) {
                $item_status = $this->alephTranslator->tab15Translate(
                    $sub_library_code,
                    $item_status_code,
                    $item_process_status
                );
            } else {
                $item_status = [
                    'opac' => 'Y',
                    'request' => 'C',
                    'desc' => (string)$z30->{'z30-item-status'},
                    'sub_lib_desc' => (string)$z30->{'z30-sub-library'},
                ];
            }
            /* @phpstan-ignore-next-line */
            if ($item_status['opac'] != 'Y') {
                continue;
            }
            $availability = false;
            $collection = (string)$z30->{'z30-collection'};
            $collection_desc = ['desc' => $collection];
            /* @phpstan-ignore-next-line */
            if ($this->alephTranslator) {
                $collection_code = (string)$item->{'z30-collection-code'};
                $collection_desc = $this->alephTranslator->tab40Translate(
                    $collection_code,
                    $sub_library_code
                );
            }
            $holdType = 'hold';
            $requested = false;
            $duedate = null;
            $addLink = false;
            $status = (string)$item->{'status'};
            if (in_array($status, $this->available_statuses)) {
                $availability = true;
            }
            /* @phpstan-ignore-next-line */
            if ($item_status['request'] == 'Y' && $availability == false) {
                $addLink = true;
            }
            if (!empty($patron)) {
                $hold_request = $item->xpath('info[@type="HoldRequest"]/@allowed');
                $addLink = ($hold_request[0] == 'Y');
                if (!$addLink) {
                    $hold_request = $item->xpath('info[@type="ShortLoan"]/@allowed');
                    if ($hold_request[0] == 'Y') {
                        $holdType = 'shortloan';
                        $addLink = true;
                    }
                }
            }
            $statuses = explode(';', $status, 2);
            $dueDateRegEx = '/([0-9]*\\/[a-zA-Z0-9]*\\/[0-9]*)/';
            $matches = [];
            if (preg_match($dueDateRegEx, $statuses[0], $matches)) {
                $duedate = $this->parseDate($matches[1]);
                $status = (count($statuses) > 1) ? $statuses[1] : null;
            }
            $item_id = $item->attributes()->href;
            $item_id = substr($item_id, strrpos($item_id, '/') + 1);
            $note = (string)$z30->{'z30-note-opac'};
            $fullStatus = !empty($duedate)
                ? $this->translate('holding_due_date') . ' ' . $duedate
                : '';
            if (empty($fullStatus)) {
                $fullStatus = $status;
            } else {
                $fullStatus = empty($status)
                    ? $fullStatus
                    : implode(' ; ', [$fullStatus, $status]);
            }
            $holding[] = [
                'id' => $id,
                'item_id' => $item_id,
                'holdtype' => $holdType,
                'availability' => $availability,
                'availability_status' => (string)$z30->{'z30-item-status'},
                'status' => $fullStatus,
                'location' => (string)$z30->{'z30-sub-library'},
                'reserve' => 'N',
                'callnumber' => (string)$z30->{'z30-call-no'},
                'number' => (string)$z30->{'z30-inventory-number'},
                'barcode' => (string)$z30->{'z30-barcode'},
                'description' => (string)$z30->{'z30-description'},
                'item_notes' => ($note == null) ? null : [$note],
                'is_holdable' => true,
                'addLink' => $addLink,
                'linkText' => ($availability) ? 'Order' : 'Reserve',
                /* below are optional attributes*/
                'collection' => (string)$collection,
                /* @phpstan-ignore-next-line */
                'collection_desc' => (string)$collection_desc['desc'],
                'callnumber_second' => (string)$z30->{'z30-call-no-2'},
                /* @phpstan-ignore-next-line */
                'sub_lib_desc' => (string)$item_status['sub_lib_desc'],
                'no_of_loans' => (string)$z30->{'$no_of_loans'},
                'requested' => (string)$requested,
            ];
        }
        return ['holdings' => $holding, 'filters' => $filters];
    }

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @throws ILSException
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatus($id)
    {
        $statuses = $this->getHolding($id);
        foreach ($statuses['holdings'] as &$status) {
            $status['status']
                = ($status['availability'] == 1) ? 'available' : 'unavailable';
        }
        return $statuses;
    }

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array   $user    The patron array from patronLogin
     * @param array   $params  Parameters
     * @param boolean $history History
     *
     * @return array        Array of the patron's transactions on success.
     * @throws ILSException
     * @throws DateException
     */
    public function getMyTransactions($user, $params = [], $history = false)
    {
        $userId = $user['id'];

        $alephParams = [];
        if ($history) {
            $alephParams['type'] = 'history';
        }

        $itemsNoKey = $history && !isset($params['page']) ? 'no_loans'
            : 'noItems';

        // total count without details is fast
        $totalCount = count(
            $this->doRestDLFRequest(
                ['patron', $userId, 'circulationActions', 'loans'],
                $alephParams + [$itemsNoKey => self::MAX_LOANS]
            )->xpath('//loan')
        );

        // with full details and paging
        $pageSize = $params['limit'] ?? 50;
        $alephParams += [
            'view' => 'full',
            'startPos' => 1 + (isset($params['page'])
                    ? ($params['page'] - 1) * $pageSize : 0),
            $itemsNoKey => $pageSize,
        ];

        $xml = $this->doRestDLFRequest(
            ['patron', $userId, 'circulationActions', 'loans'],
            $alephParams
        );

        $transList = [];
        foreach ($xml->xpath('//loan') as $item) {
            $z36 = ($history) ? $item->z36h : $item->z36;
            $prefix = ($history) ? 'z36h-' : 'z36-';
            $z13 = $item->z13;
            $z30 = $item->z30;
            $group = $item->xpath('@href');
            $group = strrchr($group[0], '/');
            $group = $group ? substr($group, 1) : '';
            $renew = $item->xpath('@renew');

            $location = (string)$z36->{$prefix . 'pickup_location'};
            $reqnum = (string)$z36->{$prefix . 'doc-number'}
                . (string)$z36->{$prefix . 'item-sequence'}
                . (string)$z36->{$prefix . 'sequence'};

            $due = (string)$z36->{$prefix . 'due-date'};
            $checkoutDate = (string)$z36->{$prefix . 'loan-date'};
            $title = (string)$z13->{'z13-title'};
            $author = (string)$z13->{'z13-author'};
            $isbn = (string)$z13->{'z13-isbn-issn'};
            $barcode = (string)$z30->{'z30-barcode'};
            // Secondary, Aleph-specific identifier that may be useful for
            // local customizations
            $adm_id = (string)$z30->{'z30-doc-number'};
            $base = (string)$z30->{'translate-change-active-library'};

            $transaction = [
                'id' => $this->barcodeToID($barcode),
                'adm_id' => $adm_id,
                'item_id' => $group,
                'location' => $location,
                'title' => $title,
                'author' => $author,
                'isbn' => $isbn,
                'reqnum' => $reqnum,
                'barcode' => $barcode,
                'duedate' => $this->parseDate($due),
                'checkoutDate'  => $this->parseDate($checkoutDate),
                'base' => $base,
            ];
            if ($history) {
                $returned = (string)$z36->{$prefix . 'returned-date'};
                $transaction['returnDate'] = $this->parseDate($returned);
            } else {
                $renewable = $renew[0] == 'Y';
                $transaction['renewable'] = $renewable;
                $transaction['message'] = ($renewable) ? '' : 'renew_item_no';
            }
            $transList[] = $transaction;
        }

        $key = ($history) ? 'transactions' : 'records';

        // workaround for Aleph when it sometimes returns empty list
        // for total count query
        if ($totalCount == 0 && count($transList) == $pageSize) {
            $totalCount = self::MAX_LOANS;
        }

        return [
            'count' => $totalCount,
            $key => $transList,
        ];
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $user The patron array from patronLogin
     *
     * @return array      Array of the patron's holds on success.
     * @throws ILSException
     * @throws DateException
     */
    public function getMyHolds($user)
    {
        $userId = $user['id'];
        $holdList = [];
        $xml = $this->doRestDLFRequest(
            ['patron', $userId, 'circulationActions', 'requests', 'holds'],
            ['view' => 'full']
        );
        foreach ($xml->xpath('//hold-request') as $item) {
            $z37 = $item->z37;
            $z13 = $item->z13;
            $z30 = $item->z30;
            $delete = $item->xpath('@delete');
            $href = $item->xpath('@href');
            $type = 'hold';
            $location = (string)$z37->{'z37-pickup-location'};
            $reqnum = (string)$z37->{'z37-doc-number'}
                . (string)$z37->{'z37-item-sequence'}
                . (string)$z37->{'z37-sequence'};
            $expire = (string)$z37->{'z37-end-request-date'};
            $create = (string)$z37->{'z37-open-date'};
            $holddate = (string)$z37->{'z37-hold-date'};
            $title = (string)$z13->{'z13-title'};
            $author = (string)$z13->{'z13-author'};
            $isbn = (string)$z13->{'z13-isbn-issn'};
            $barcode = (string)$z30->{'z30-barcode'};
            $item_id = (string)$z37->{'z37-doc-number'}
                . (string)$z37->{'z37-sequence'};
            $z37_status = (string)$z37->{'z37-status'};
            $hold_item_id = (string)$z37->{'translate-change-active-library'}
                . (string)$z37->{'z37-doc-number'}
                . (string)$z37->{'z37-item-sequence'};
            $cancel_item_id = substr($href[0], strrpos($href[0], '/') + 1);
            // remove superfluous spaces in status
            $status = preg_replace("/\s[\s]+/", ' ', $item->status);
            $position = null;
            // Extract position in the hold queue from item status
            if (preg_match($this->queuePositionRegex, $status, $matches)) {
                $position = $matches['position'];
            }
            if ($holddate == '00000000') {
                $holddate = null;
            } else {
                $holddate = $this->parseDate($holddate);
            }
            $delete = ($delete[0] == 'Y');
            // Secondary, Aleph-specific identifier that may be useful for
            // local customizations
            $adm_id = (string)$z30->{'z30-doc-number'};
            $base = (string)$z30->{'translate-change-active-library'};

            $holdList[] = [
                'type' => $type,
                'item_id' => $item_id,
                'adm_id' => $adm_id,
                'hold_item_id' => $hold_item_id,
                'cancel_item_id' => $cancel_item_id,
                'location' => $location,
                'title' => $title,
                'author' => $author,
                'isbn' => $isbn,
                'reqnum' => $reqnum,
                'barcode' => $barcode,
                'id' => $this->barcodeToID($barcode),
                'expire' => $this->parseDate($expire),
                'holddate' => $holddate,
                'delete' => $delete,
                'create' => $this->parseDate($create),
                'status' => $status,
                'position' => $position,
                'z37_status' => $z37_status,
                'base' => $base,
            ];
        }
        return $holdList;
    }

    /**
     * Get Cancel Hold Details
     *
     * @param array $holdDetails A single hold array from getMyHolds
     * @param array $patron      Patron information from patronLogin
     *
     * @return string Data for use in a form field
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getCancelHoldDetails($holdDetails, $patron = [])
    {
        if ($holdDetails['delete'] && $holdDetails['z37_status'] !== 'S') {
            return $holdDetails['cancel_item_id'];
        }
        return null;
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $user The patron array
     *
     * @throws ILSException
     * @return array      Array of the patron's profile data on success.
     */
    public function getMyProfile($user)
    {
        return $this->getMyProfileDLF($user);
    }

    /**
     * Get profile information using X-server.
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $user The patron array
     *
     * @return array      Array of the patron's profile data on success.
     * @throws ILSException
     */
    public function getMyProfileX($user): ?array
    {
        $result = parent::getMyProfileX($user);
        if (isset($result['expire'])) {
            $result['expiration_date'] = $this->parseDate($result['expire']);
        }
        return $result;
    }

    /**
     * Get profile information using DLF service.
     *
     * @param array $user The patron array
     *
     * @throws ILSException
     * @return array      Array of the patron's profile data on success.
     */
    public function getMyProfileDLF($user)
    {
        $userId = $user['id'];
        $profile = [
            'id' => $userId,
            'cat_username' => $userId,
        ];
        // address
        $xml = $this->doRestDLFRequest(
            ['patron', $userId, 'patronInformation', 'address']
        );
        $address = $xml->xpath('//address-information')[0];
        foreach ($this->addressMappings as $key => $value) {
            $value = (string)$address->{$value};
            $profile[$key] = !empty($value) ? $value : null;
        }
        // parse the fullname into last and first name
        $fullName = $profile['fullname'];
        if ($fullName != null) {
            if (!str_contains($fullName, ',')) {
                $profile['lastname'] = $fullName;
                $profile['firstname'] = null;
            } else {
                [$profile['lastname'], $profile['firstname']]
                    = explode(',', $fullName);
            }
        }
        // registration status
        $xml = $this->doRestDLFRequest(
            ['patron', $userId, 'patronStatus', 'registration']
        );
        $status = $xml->xpath('//institution/z305-bor-status');
        $expiry = $xml->xpath('//institution/z305-expiry-date');
        $profile['expiration_date'] = $this->parseDate($expiry[0]);
        $profile['group'] = !empty($status[0]) ? $status[0] : null;
        return $profile;
    }

    /**
     * Get blocks
     *
     * @param array $user The patron array
     *
     * @throws ILSException
     * @return array      Array of the patron's profile data on success.
     */
    public function getMyBlocks(array $user)
    {
        $blocks = [];
        if ($this->showAlephLabelBlocks) {
            $xml = $this->doRestDLFRequest(
                ['patron', $user['id'], 'patronStatus', 'blocks']
            );
            $blocksArray = (array)$xml->{'blocks_messages'}->{'global-blocks'};
            foreach ($blocksArray as $block) {
                $blocks[] = [
                    'label' => (string)$block,
                ];
            }
            return $blocks;
        }
        $user['college'] ??= $this->useradm;
        $xml = $this->doXRequest(
            'bor-info',
            [
                'loans' => 'N', 'cash' => 'N', 'hold' => 'N',
                'library' => $user['college'], 'bor_id' => $user['id'],
            ],
            true
        );
        $parents = ['z303', 'z305'];
        foreach ($parents as $parent) {
            for ($i = 1; $i <= 3; $i++) {
                $block = (string)$xml->{$parent}->{$parent . '-delinq-' . $i};
                if (empty($block) || $block == '00') {
                    continue;
                }
                $updated = (string)$xml->{$parent}->{$parent . '-delinq-' . $i . '-update-date'};
                $blockId = 'block_' . $block;
                $label = $this->translate(
                    'ILSMessages::' . (!empty($this->source) ? $this->source . '.' : '') . $blockId
                );
                $blocks[] = [
                    'id' => $blockId,
                    'label' => $label,
                    'updated' => $this->parseDate($updated),
                ];
            }
        }
        return $blocks;
    }

    /**
     * Get hold order in queue
     *
     * @param array $patron   Patron information returned by the patronLogin method.
     * @param array $holdInfo Optional array, only passed in when getting a list
     * in the context of placing or editing a hold.  When placing a hold, it contains
     * most of the same values passed to placeHold, minus the patron data.  When
     * editing a hold it contains all the hold information returned by getMyHolds.
     * May be used to limit the pickup options or may be ignored.  The driver must
     * not add new options to the return array based on this data or other areas of
     * VuFind may behave incorrectly.
     *
     * @throws ILSException
     *
     * @return int
     */
    public function getHoldOrderInQueue($patron, $holdInfo)
    {
        if ($holdInfo == null) {
            return 0;
        }
        $details = $this->getHoldingInfoForItem(
            $patron['id'],
            $holdInfo['id'],
            $holdInfo['item_id']
        );
        return $details['order'];
    }

    /**
     * Support method for placeHold -- get holding info for an item.
     *
     * @param string $patronId Patron ID
     * @param string $id       Bib ID
     * @param string $group    Item ID
     *
     * @return array
     */
    public function getHoldingInfoForItem($patronId, $id, $group)
    {
        [$bib, $sys_no] = $this->parseId($id);
        $resource = $bib . $sys_no;
        $xml = $this->doRestDLFRequest(
            ['patron', $patronId, 'record', $resource, 'items', $group]
        );
        $holdRequestAllowed = $xml->xpath(
            "//item/info[@type='HoldRequest']/@allowed"
        );
        $holdRequestAllowed = !empty($holdRequestAllowed)
            && $holdRequestAllowed[0] == 'Y';
        if ($holdRequestAllowed) {
            return $this->extractHoldingInfoForItem($xml);
        }
        $shortLoanAllowed = $xml->xpath("//item/info[@type='ShortLoan']/@allowed");
        $shortLoanAllowed = !empty($shortLoanAllowed) && $shortLoanAllowed[0] == 'Y';
        if ($shortLoanAllowed) {
            return $this->extractShortLoanInfoForItem($xml);
        }
        throw new \Exception('Hold request or short loan is not alllowed');
    }

    /**
     * Place short loan request
     *
     * @param array $details details
     *
     * @return array
     */
    public function placeShortLoan($details)
    {
        [$bib, $sys_no] = $this->parseId($details['id']);
        $recordId = $bib . $sys_no;
        $slot = $details['slot'];
        $itemId = $details['item_id'];
        $patron = $details['patron'];
        $patronId = $patron['id'];
        $body = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<short-loan-parameters></short-loan-parameters>'
        );
        $body->addChild('request-slot', $slot);
        $data = 'post_xml=' . $body->asXML();
        try {
            $result = $this->doRestDLFRequest(
                ['patron', $patronId, 'record', $recordId,
                'items', $itemId, 'shortLoan'],
                null,
                'PUT',
                $data
            );
        } catch (\Exception $ex) {
            return ['success' => false, 'sysMessage' => $ex->getMessage];
        }
        return ['success' => true];
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
        $xml = $this->doRestDLFRequest(
            ['patron', $patron['id'], 'circulationActions',
            'requests', 'bookings'],
            ['view' => 'full']
        );
        $results = [];
        foreach ($xml->xpath('//booking-request') as $item) {
            $delete = $item->xpath('@delete');
            $href = $item->xpath('@href');
            $item_id = substr($href[0], strrpos($href[0], '/') + 1);
            $z13 = $item->z13;
            $z37 = $item->z37;
            $z30 = $item->z30;
            $barcode = (string)$z30->{'z30-barcode'};
            $startDate = (string)$z37->{'z37-booking-start-date'};
            $startTime = (string)$z37->{'z37-booking-start-hour'};
            $endDate = (string)$z37->{'z37-booking-end-date'};
            $endTime = (string)$z37->{'z37-booking-end-hour'};
            $callnumber = $z30->{'z30-call-no'};
            $start = $this->parseDate($startDate)
                . ' ' . $this->parseTime($startTime);
            $end = $this->parseDate($endDate)
                . ' ' . $this->parseTime($endTime);
            $delete = ($delete[0] == 'Y');
            $id = (string)$z13->{'z13-doc-number'};
            $adm_id = (string)$z30->{'z30-doc-number'};
            $sortKey = (string)$startDate[0] . $item_id;
            $results[$sortKey] = [
                'id'         => $this->barcodeToID($barcode),
                'adm_id'     => $adm_id,
                'start'      => $start,
                'end'        => $end,
                'delete'     => $delete,
                'item_id'    => $item_id,
                'barcode'    => $barcode,
                'callnumber' => $callnumber,
            ];
        }
        ksort($results);
        $results = array_values($results);
        return $results;
    }

    /**
     * Return short loan requests for patron
     *
     * @param array $patron patron
     *
     * @return array
     */
    public function getMyShortLoanLinks($patron)
    {
        $links = $this->config['ShortLoanLinks'] ?? [];
        $result = [];
        foreach ($links as $id => $label) {
            $result[] = [
                'id' => $id,
                'label' => $label,
            ];
        }
        return $result;
    }

    /**
     * Get details for canceling short loan request
     *
     * @param array $details details
     *
     * @return array|null
     */
    public function getCancelShortLoanDetails($details)
    {
        if ($details['delete']) {
            return $details['item_id'];
        } else {
            return null;
        }
    }

    /**
     * Cancel short loan requests
     *
     * @param array $details details
     *
     * @return array
     */
    public function cancelShortLoans($details)
    {
        $patron = $details['patron'];
        $patronId = $patron['id'];
        $count = 0;
        $statuses = [];
        foreach ($details['details'] as $id) {
            try {
                $result = $this->doRestDLFRequest(
                    ['patron', $patronId, 'circulationActions',
                    'requests', 'bookings', $id],
                    null,
                    'DELETE'
                );
            } catch (\Exception $ex) {
                $statuses[$id] = [
                    'success' => false,
                    'status' => 'cancel_hold_failed',
                    'sysMessage' => (string)$ex->getMessage(),
                ];
            }
            $count++;
            $statuses[$id] = [
                'success' => true,
                'status' => 'cancel_hold_ok',
            ];
        }
        $statuses['count'] = $count;
        return $statuses;
    }

    /**
     * Return link to pay all fines
     *
     * @param array $patron patron
     * @param int   $fine   fine to pay
     *
     * @return string
     */
    public function getMyPaymentLink($patron, $fine)
    {
        $paymentUrl  = $this->config['Payment']['url'] ?? null;
        if ($paymentUrl == null) {
            return null;
        }
        $params = [
            'id'     => $patron['id'],
            'adm'    => $this->useradm,
            'amount' => $fine,
            'time'   => time(),
        ];
        $query = http_build_query($params);
        $url = $paymentUrl . '?' . $query;
        return $url;
    }

    /**
     * Get link for prolonging of registration
     *
     * @param array $patron patron
     *
     * @return string|null
     */
    public function getMyProlongRegistrationLink($patron): ?string
    {
        if (!isset($patron['expiration_date'])) {
            return null;
        }
        $url = $this->config['ProlongRegistration']['url'] ?? null;
        if ($url == null) {
            return null;
        }
        $status = $this->config['ProlongRegistration']['status'] ?? '03';
        $from = $this->config['ProlongRegistration']['from'] ?? 'cpk';
        $hmac = $this->config['ProlongRegistration']['hmac'] ?? null;
        $expire = $this->dateConverter->parseDisplayDate(
            $this->parseDate($patron['expiration_date'])
        );
        $dateDiff = date_diff(date_create(), $expire);
        $daysDiff =  (($dateDiff->invert == 0) ? 1 : -1) * $dateDiff->days;
        if ($daysDiff > 31) {
            return null;
        }
        $hash = hash_hmac('sha256', $patron['id'], $hmac, true);
        $hash = base64_encode($hash);
        $params = [
            'id'           => $patron['id'],
            'status_cten'  => $status,
            'from'         => $from,
            'hmac'         => $hash,
        ];
        $query = http_build_query($params);
        $url = $url . '?' . $query;
        return $url;
    }

    /**
     * Convert a barcode to an item ID.
     *
     * @param string $barcode Barcode
     *
     * @return string|null
     */
    public function barcodeToID($barcode)
    {
        // we use SolrIdResolver instead
        return null;
    }

    /**
     * Get Patron ILL requests
     *
     * This is responsible for retrieving all ILL requests by a specific patron.
     *
     * @param array $user The patron array from patronLogin
     *
     * @throws ILSException
     * @return array           Array of the patron's ILL requests
     */
    public function getMyILLRequests($user): array
    {
        $userId = $user['id'];
        $params = ['view' => 'full', 'type' => 'active'];
        $count = 0;
        $xml = $this->doRestDLFRequest(
            ['patron', $userId,
            'circulationActions', 'requests', 'ill'],
            $params
        );
        $loans = [];
        $formats = $this->config['ILLRequestMapping']['format'] ?? [];
        foreach ($xml->xpath('//ill-request') as $item) {
            $z13 = $item->z13;
            $z410 = $item->z410;
            $create = (string)$z410->{'z410-open-date'};
            $expire = (string)$z410->{'z410-last-interest-date'};
            $media = (string)$z410->{'z410-media'};
            $format = $formats[$media] ?? 'Unknown';
            $loans[] = [
                'format' => $format,
                'docno' => (string)$z13->{'z13-doc-number'},
                'author' => (string)$z13->{'z13-author'},
                'title' => (string)$z13->{'z13-title'},
                'imprint' => (string)$z13->{'z13-imprint'},
                'article_title' => (string)$item->{'title-of-article'},
                'article_author' => (string)$item->{'author-of-article'},
                'price' => (string)$item->{'z13u-additional-bib-info-1'},
                'pickup_location' => (string)$z410->{'z410-pickup-location'},
                'media' => $media,
                'create' => $this->parseDate($create),
                'expire' => $this->parseDate($expire),
            ];
        }
        return $loans;
    }

    /**
     * Get supported blank ILL request types
     *
     * @return string[]
     */
    public function getBlankIllRequestTypes(): array
    {
        return [
            'monography',
            'serial',
        ];
    }

    /**
     * Get forms for ILL Request
     *
     * This is responsible for retrieving all ILL request forms
     *
     * @param array  $patron The patron array from patronLogin
     * @param string $type   Request type - monography or serial
     *
     * @throws ILSException
     * @return array           forms
     */
    public function getFormForBlankILLRequest($patron, $type): array
    {
        $recordType = null;
        if ($type == 'monography') {
            $recordType = 'MN';
        } elseif ($type == 'serial') {
            $recordType = 'SE';
        }
        $patronId = $patron['id'];
        $path = ['patron', $patronId, 'record', $recordType, 'ill'];
        $result = $this->doRestDLFRequest($path);
        $form = [];
        foreach ($result->{'ill-information'}->children() as $element) {
            $field = $element->getName();
            $attributes = $element->attributes();
            $required = ($attributes['usage'] ?? null) == 'Mandatory';
            if ($element->count() == 0) {
                $form[$field] = [
                    'type'     => ($field != 'last-interest-date') ? 'text' : 'future_date',
                    'label'    => self::ILL_BLANK_FORM_LABEL_PREFIX . $field,
                    'required' => $required,
                ];
            } elseif ($field == 'ill-unit') {
                $options = [];
                foreach ($element->{'pickup-locations'}->{'pickup-location'} as $location) {
                    $locAttributes = $location->attributes();
                    $code = (string)$locAttributes['code'];
                    $text = (string)$location[0];
                    $options[$code] = $text;
                }
                $form[$field] = $this->createIllFormElementFromOptions($field, $required, $options);
            } else {
                $childs = $element->children();
                $field = $childs[0]->getName();
                $options = [];
                foreach ($childs as $child) {
                    $code = null;
                    $text = null;
                    foreach ($child->children() as $subChild) {
                        if (str_ends_with($subChild->getName(), '-code')) {
                            $code = (string)$subChild->xpath('text()')[0];
                        }
                        if (str_ends_with($subChild->getName(), '-text')) {
                            $text = (string)$subChild->xpath('text()')[0];
                        }
                    }
                    if ($code != null && $text != null) {
                        $options[$code] = $text;
                    }
                }
                $form[$field] = $this->createIllFormElementFromOptions($field, $required, $options);
            }
        }
        $fieldsConfig = $this->config['BlankILLRequestFor' . ucfirst($type) . 'Fields'];
        foreach ($fieldsConfig['fields'] as $id => $config) {
            $options = $fieldsConfig[$id] ?? null;
            $form[$id] = $this->createIllFormElementFromConfig($config, $options);
        }
        $groupsConfig = $this->config['BlankILLRequestFor' . ucfirst($type) . 'Groups'];
        $groups = [];
        foreach ($groupsConfig as $id => $group) {
            $result = [
                'heading' => $group['heading'] ?? null,
                'text' => $group['text'] ?? null,
            ];
            if (isset($group['fields'])) {
                $fields = explode(',', $group['fields']);
                $fieldSet = [];
                foreach ($fields as $field) {
                    $fieldSet[$field] = $form[$field];
                }
                $result['fields'] = $fieldSet;
            }
            $groups[$id] = $result;
        }
        $hiddenFields = [];
        foreach ($form as $id => $config) {
            if ($config['type'] == 'hidden') {
                $hiddenFields[$id] = $config;
            }
        }
        $groups['hidden'] = [
            'fields' => $hiddenFields,
        ];
        return $groups;
    }

    /**
     * Place blank ILL request
     *
     * @param array $patron patron
     * @param array $data   data
     *
     * @return array result
     * @throws DateException
     * @throws ILSException
     */
    public function placeBlankILLRequest($patron, $data): array
    {
        $type = $data['type'];
        $form = $this->getFormForBlankILLRequest($patron, $type);
        $patronId = $patron['id'];
        $illDom = new \DOMDocument('1.0', 'UTF-8');
        $illRoot = $illDom->createElement('ill-parameters');
        $illRootNode = $illDom->appendChild($illRoot);
        $variableFields = [];
        foreach ($form as $id => $group) {
            $fields = $group['fields'] ?? [];
            foreach ($fields as $key => $config) {
                $value = $data[$key] ?? null;
                if ($config['type'] == 'hidden') {
                    $value = $config['value'];
                }
                if ($value == null) {
                    continue;
                }
                $target = $config['target'] ?? 'xml';
                if ($target == 'none') {
                    continue;
                }
                if ($config['type'] == 'date' || $config['type'] == 'future_date') {
                    $value = $this->dateConverter->convertFromDisplayDate('Ymd', $value);
                }
                if ($target == 'variableField' && isset($config['variableField'])) {
                    $variableFields[] = [
                        'variableField' => $config['variableField'],
                        'value' =>  $value,
                    ];
                    continue;
                }
                $element = $illDom->createElement($key);
                $element->appendChild($illDom->createTextNode($this->escapeTextNode($value)));
                $illRootNode->appendChild($element);
            }
        }
        $xml = $illDom->saveXML();
        $this->getLogger()->debug($xml);
        try {
            $request = ($type == 'serial') ? 'SE' : 'MN';
            $path = ['patron', $patronId, 'record', $request, 'ill'];
            $result = $this->doRestDLFRequest(
                $path,
                null,
                'PUT',
                'post_xml=' . $xml
            );
        } catch (\Exception $ex) {
            return ['success' => false, 'sysMessage' => $ex->getMessage()];
        }
        $baseAndDocNumber = $result->{'create-ill'}->{'request-number'};
        $base = substr($baseAndDocNumber, 0, 5);
        $docNum = substr($baseAndDocNumber, 5);
        if (empty($variableFields)) {
            return ['success' => true, 'id' => $docNum];
        }
        $findDocParams = ['base' => $base, 'doc_num' => $docNum];
        $document = $this->doXRequest('find-doc', $findDocParams, true);
        foreach ($variableFields as $variableField) {
            $target = $variableField['variableField'];
            $value = $variableField['value'];
            $id = substr($target, 0, 3);
            $i1 = substr($target, 3, 1) ?: ' ';
            $i2 = substr($target, 4, 1) ?: ' ';
            $label = substr($target, 5, 1) ?: 'a';
            $variableField = $document->{'record'}->{'metadata'}->{'oai_marc'}->addChild('varfield');
            $variableField->addAttribute('id', $id);
            $variableField->addAttribute('i1', $i1);
            $variableField->addAttribute('i2', $i2);
            $subfield = $variableField->addChild('subfield', $value);
            $subfield->addAttribute('label', $label);
        }
        $updateDocParams = ['library' => $base, 'doc_num' => $docNum];
        $xml = $document->asXml();
        $updateDocParams['xml_full_req'] = $xml;
        $this->getLogger()->debug($xml);
        $updateDocParams['doc_action'] = 'UPDATE';
        try {
            $update = $this->doXRequestUsingPost('update-doc', $updateDocParams, true);
        } catch (\Exception $ex) {
            return ['success' => false, 'sysMessage' => $ex->getMessage()];
        }
        return ['success' => true, 'id' => $docNum];
    }

    /**
     * Extract holdings for items from XML response
     *
     * @param $xml xml to process
     *
     * @return array
     * @throws \VuFind\Exception\ILS
     */
    protected function extractHoldingInfoForItem($xml)
    {
        $locations = [];
        $part = $xml->xpath('//pickup-locations');
        if ($part) {
            foreach ($part[0]->children() as $node) {
                $arr = $node->attributes();
                $code = (string)$arr['code'];
                $loc_name = (string)$node;
                $locations[$code] = $loc_name;
            }
        } else {
            throw new ILSException('No pickup locations');
        }
        $requests = 0;
        $str = $xml->xpath('//item/queue/text()');
        if ($str != null) {
            [$requests] = explode(' ', trim($str[0]));
        }
        $date = $xml->xpath('//last-interest-date/text()');
        $date = $date[0];
        $date = '' . substr($date, 6, 2) . '.' . substr($date, 4, 2) . '.'
            . substr($date, 0, 4);
        return [
            'pickup-locations' => $locations,
            'last-interest-date' => $date,
            'order' => $requests + 1,
        ];
    }

    /**
     * Extract short loan info for items from XML response
     *
     * @param $xml xml to process
     *
     * @return array
     * @throws \VuFind\Exception\ILS
     */
    protected function extractShortLoanInfoForItem($xml)
    {
        $shortLoanInfo = $xml->xpath("//item/info[@type='ShortLoan']");
        $slots = [];
        foreach ($shortLoanInfo[0]->{'short-loan'}->{'slot'} as $slot) {
            $numOfItems = (int)$slot->{'num-of-items'};
            $numOfOccupied = (int)$slot->{'num-of-occupied'};
            $available = ($numOfItems - $numOfOccupied) > 0;
            $startDate = $this->parseDate((string)$slot->{'start'}->{'date'});
            $startTime = $slot->{'start'}->{'hour'};
            $endTime = $slot->{'end'}->{'hour'};
            $id = (string)$slot->attributes()->id[0];
            if (!isset($slots[$startDate])) {
                $slots[$startDate] = [];
            }
            $slots[$startDate][] = [
                'slot' => $id,
                'start_time' => $this->parseTime($startTime),
                'end_time' => $this->parseTime($endTime),
                'available' => $available,
            ];
        }
        $result = [
            'type'       => 'short',
            'slots'      => $slots,
        ];
        return $result;
    }

    /**
     * Parse a time.
     *
     * @param string $time time to parse
     *
     * @return string formatted time
     */
    protected function parseTime($time)
    {
        if (preg_match(self::TIME_FORMAT, $time) === 0) {
            throw new \Exception("Invalid time: $time");
        }
        return substr($time, 0, 2) . ':'
            . substr($time, 2, 2);
    }

    /**
     * Parse a date.
     *
     * @param string $date          Date to parse
     * @param bool   $ignoreInvalid Ignore invalid date and return null instead
     *
     * @return string|null
     */
    public function parseDate($date, bool $ignoreInvalid = true): string|null
    {
        if (empty($date)) {
            return null;
        }
        foreach (self::DATE_FORMATS as $regex => $format) {
            if (preg_match($regex, $date) === 1) {
                try {
                    return $this->dateConverter
                        ->convertToDisplayDate($format, $date);
                } catch (\VuFind\Date\DateException $de) {
                    if ($ignoreInvalid) {
                        return null;
                    }
                    throw $de;
                }
            }
        }
        // always throw exception for unknown date format
        throw new \VuFind\Date\DateException("Invalid date format: $date");
    }

    /**
     * Escape text node for Aleph
     *
     * @param string $text text to escape
     *
     * @return string
     */
    protected function escapeTextNode($text)
    {
        if ($text == null) {
            return '';
        }
        return str_replace('&', ' AND ', $text);
    }

    /**
     * Perform an XServer request.
     *
     * @param string $op     Operation
     * @param array  $params Parameters
     * @param bool   $auth   Include authentication?
     *
     * @return \SimpleXMLElement
     */
    protected function doXRequestUsingPost($op, $params, $auth = true)
    {
        $url = "http://$this->host:$this->xport/X?";
        $body = '';
        $sep = '';
        $params['op'] = $op;
        if ($auth) {
            $params['user_name'] = $this->wwwuser;
            $params['user_password'] = $this->wwwpasswd;
        }
        foreach ($params as $key => $value) {
            $body .= $sep . $key . '=' . urlencode($value);
            $sep = '&';
        }
        $result = $this->doHTTPRequest($url, 'POST', $body);
        if ($result->error) {
            if (
                $op == 'update-doc' && preg_match(
                    '/Document: [0-9]+ was updated successfully\\./',
                    trim($result->error)
                ) === 1
            ) {
                return $result;
            }
            if ($this->debug_enabled) {
                $this->debug("XServer error, URL is $url, error message: $result->error.");
            }
            throw new ILSException("XServer error: $result->error.");
        }
        return $result;
    }

    /**
     * Create form element from options - convert select with only one option to
     * hidden element
     *
     * @param string $field    Field name
     * @param bool   $required Required
     * @param array  $options  Options
     *
     * @return array
     */
    protected function createIllFormElementFromOptions($field, $required, $options)
    {
        if (count($options) == 1) {
            return [
                'type'     => 'hidden',
                'label'    => self::ILL_BLANK_FORM_LABEL_PREFIX . $field,
                'value'  => array_key_first($options),
            ];
        }
        return [
            'type'     => 'select',
            'label'    => $field,
            'required' => $required,
            'options'  => $options,
        ];
    }

    /**
     * Create form element from configuration
     *
     * @param array      $config  configuration of element
     * @param array|null $options options for select
     *
     * @return array
     */
    protected function createIllFormElementFromConfig($config, $options)
    {
        $config = explode(':', $config);
        $type = $config[0];
        $label = $config[1];
        $spec = $config[2] ?? 'optional';
        $target = $config[3] ?? 'xml';
        $value = null;
        if ($type == 'hidden') {
            $value = $config[4] ?? null;
        }
        $variableField = null;
        if ($target == 'variableField') {
            $variableField = $config[4] ?? null;
        }
        $result = [
            'type'     => $type,
            'label'    => $label,
            'required' => $spec == 'required',
            'options'  => $options,
            'target'   => $target,
        ];
        if (!empty($options)) {
            $result['options'] = $options;
        }
        if ($variableField != null) {
            $result['variableField'] = $variableField;
        }
        if ($value != null) {
            $result['value'] = $value;
        }
        return $result;
    }

    /**
     * Set source from multibackend
     *
     * @param string $source Source identifier
     *
     * @return void
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }
}
