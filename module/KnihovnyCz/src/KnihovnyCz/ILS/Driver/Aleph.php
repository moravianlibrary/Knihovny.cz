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

    const DATE_FORMATS = [
        '/^[0-9]{8}$/' => 'Ynd',
        '/^[0-9]+\/[A-Za-z]{3}\/[0-9]{4}$/' => 'd/M/Y',
        '/^[0-9]+\/[A-Za-z]{3}\/[0-9]{2}$/' => 'd/M/y',
        '/^[0-9]+\/[0-9]+\/[0-9]{4}$/' => 'd/m/Y',
        '/^[0-9]+\/[0-9]+\/[0-9]{2}$/' => 'd/m/y',
    ];

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
                'holdtype'            => 'hold',
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
     * Parse a date.
     *
     * @param string $date Date to parse
     *
     * @return string
     */
    public function parseDate($date)
    {
        foreach (self::DATE_FORMATS as $regex => $format) {
            if (preg_match($regex, $date) === 1) {
                return $this->dateConverter
                    ->convertToDisplayDate($format, $date);
            }
        }
        throw new \Exception("Invalid date: $date");
    }
}
