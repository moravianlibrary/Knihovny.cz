<?php

/**
 * Class Aleph
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
 * @package  KnihovnyCz\ILS\Driver
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
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

    public const DATE_FORMATS = [
        '/^[0-9]{8}$/' => 'Ynd',
        '/^[0-9]+\/[A-Za-z]{3}\/[0-9]{4}$/' => 'd/M/Y',
        '/^[0-9]+\/[A-Za-z]{3}\/[0-9]{2}$/' => 'd/M/y',
        '/^[0-9]+\/[0-9]+\/[0-9]{4}$/' => 'd/m/Y',
        '/^[0-9]+\/[0-9]+\/[0-9]{2}$/' => 'd/m/y',
        '/^[0-9]+\. [0-9]+\. [0-9]{4}$/' => 'd. m. Y',
    ];

    protected const TIME_FORMAT = '/^[0-2][0-9][0-6][0-9]$/';

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
        }
        if ($func == 'getMyPaymentLink') {
            return [];
        }
        return parent::getConfig($func, $params);
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
        $xml = $this->doRestDLFRequest(['record', $resource, 'items'], $params);
        if (!empty($xml->{'items'})) {
            $items = $xml->{'items'}->{'item'};
        } else {
            $items = [];
        }
        foreach ($items as $item) {
            $item_status_code    = (string)$item->{'z30-item-status-code'}; // $isc
            // $ipsc:
            $item_process_status = (string)$item->{'z30-item-process-status-code'};
            $sub_library_code    = (string)$item->{'z30-sub-library-code'}; // $slc
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
                    'opac'         => 'Y',
                    'request'      => 'C',
                    'desc'         => (string)$z30->{'z30-item-status'},
                    'sub_lib_desc' => (string)$z30->{'z30-sub-library'}
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
            $dueDateRegEx = "/([0-9]*\\/[a-zA-Z0-9]*\\/[0-9]*)/";
            $matches = [];
            if (preg_match($dueDateRegEx, $statuses[0], $matches)) {
                $duedate = $this->parseDate($matches[1]);
                $status = (count($statuses) > 1) ? $statuses[1] : null;
            }
            $item_id = $item->attributes()->href;
            $item_id = substr($item_id, strrpos($item_id, '/') + 1);
            $note    = (string)$z30->{'z30-note-opac'};
            $fullStatus = !empty($duedate)
                ? $this->translate('holding_due_date') . ' ' . $duedate
                : '';
            if (empty($fullStatus)) {
                $fullStatus = $status;
            } else {
                $fullStatus = empty($status)
                    ? $fullStatus
                    : implode(" ; ", [$fullStatus, $status]);
            }
            $holding[] = [
                'id'                  => $id,
                'item_id'             => $item_id,
                'holdtype'            => $holdType,
                'availability'        => $availability,
                'availability_status' => (string)$z30->{'z30-item-status'},
                'status'              => $fullStatus,
                'location'            => (string)$z30->{'z30-sub-library'},
                'reserve'             => 'N',
                'callnumber'          => (string)$z30->{'z30-call-no'},
                'number'              => (string)$z30->{'z30-inventory-number'},
                'barcode'             => (string)$z30->{'z30-barcode'},
                'description'         => (string)$z30->{'z30-description'},
                'notes'               => ($note == null) ? null : [$note],
                'is_holdable'         => true,
                'addLink'             => $addLink,
                /* below are optional attributes*/
                'collection'          => (string)$collection,
                /* @phpstan-ignore-next-line */
                'collection_desc'     => (string)$collection_desc['desc'],
                'callnumber_second'   => (string)$z30->{'z30-call-no-2'},
                /* @phpstan-ignore-next-line */
                'sub_lib_desc'        => (string)$item_status['sub_lib_desc'],
                'no_of_loans'         => (string)$z30->{'$no_of_loans'},
                'requested'           => (string)$requested
            ];
        }
        return $holding;
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
     * @throws DateException
     * @throws ILSException
     * @return array        Array of the patron's transactions on success.
     */
    public function getMyTransactions($user, $params = [], $history = false)
    {
        $userId = $user['id'];

        $alephParams = [];
        if ($history) {
            $alephParams['type'] = 'history';
        }

        // total count without details is fast
        $totalCount = count(
            $this->doRestDLFRequest(
                ['patron', $userId, 'circulationActions', 'loans'],
                $alephParams
            )->xpath('//loan')
        );

        // with full details and paging
        $pageSize = $params['limit'] ?? 50;
        $itemsNoKey = $history && !isset($params['page']) ? 'no_loans'
            : 'noItems';
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
            $group = strrchr($group[0], "/");
            $group = $group ? substr($group, 1) : '';
            $renew = $item->xpath('@renew');

            $location = (string)$z36->{$prefix . 'pickup_location'};
            $reqnum = (string)$z36->{$prefix . 'doc-number'}
                . (string)$z36->{$prefix . 'item-sequence'}
                . (string)$z36->{$prefix . 'sequence'};

            $due = (string)$z36->{$prefix . 'due-date'};
            $title = (string)$z13->{'z13-title'};
            $author = (string)$z13->{'z13-author'};
            $isbn = (string)$z13->{'z13-isbn-issn'};
            $barcode = (string)$z30->{'z30-barcode'};
            // Secondary, Aleph-specific identifier that may be useful for
            // local customizations
            $adm_id = (string)$z30->{'z30-doc-number'};

            $transaction = [
                'id' => $this->barcodeToID($barcode),
                'adm_id'   => $adm_id,
                'item_id' => $group,
                'location' => $location,
                'title' => $title,
                'author' => $author,
                'isbn' => $isbn,
                'reqnum' => $reqnum,
                'barcode' => $barcode,
                'duedate' => $this->parseDate($due),
                'renewable' => $renew[0] == "Y",
            ];
            if ($history) {
                $issued = (string)$z36->{$prefix . 'loan-date'};
                $returned = (string)$z36->{$prefix . 'returned-date'};
                $transaction['checkoutDate'] = $this->parseDate($issued);
                $transaction['returnDate'] = $this->parseDate($returned);
            }
            $transList[] = $transaction;
        }

        $key = ($history) ? 'transactions' : 'records';

        return [
            'count' => $totalCount,
            $key => $transList
        ];
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
            if (strpos($fullName, ",") === false) {
                $profile['lastname'] = $fullName;
                $profile['firstname'] = "";
            } else {
                [$profile['lastname'], $profile['firstname']]
                    = explode(",", $fullName);
            }
        }
        // registration status
        $xml = $this->doRestDLFRequest(
            ['patron', $userId, 'patronStatus', 'registration']
        );
        $status = $xml->xpath("//institution/z305-bor-status");
        $expiry = $xml->xpath("//institution/z305-expiry-date");
        $profile['expiration_date'] = $this->parseDate($expiry[0]);
        $profile['group'] = !empty($status[0]) ? $status[0] : null;
        return $profile;
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
        throw new \Exception("Hold request or short loan is not alllowed");
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
                "PUT",
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
            ["view" => "full"]
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
            $delete = ($delete[0] == "Y");
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
                'callnumber' => $callnumber
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
                    "DELETE"
                );
            } catch (\Exception $ex) {
                $statuses[$id] = [
                    'success' => false,
                    'status' => 'cancel_hold_failed',
                    'sysMessage' => (string)$ex->getMessage()
                ];
            }
            $count++;
            $statuses[$id] = [
                'success' => true,
                'status' => 'cancel_hold_ok'
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
     * @return string
     */
    public function getMyProlongRegistrationLink($patron)
    {
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
            'from'         => 'cpk',
            'hmac'         => $hash,
        ];
        $query = http_build_query($params);
        $url = $url . '?' . $query;
        return $url;
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
        $date = "" . substr($date, 6, 2) . "." . substr($date, 4, 2) . "."
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
     * @param string $date Date to parse
     *
     * @return string
     */
    public function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }
        foreach (self::DATE_FORMATS as $regex => $format) {
            if (preg_match($regex, $date) === 1) {
                return $this->dateConverter
                    ->convertToDisplayDate($format, $date);
            }
        }
        throw new \Exception("Invalid date: $date");
    }
}
