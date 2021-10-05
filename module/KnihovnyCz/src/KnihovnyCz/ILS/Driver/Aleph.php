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
 * @link https://knihovny.cz Main Page
 */
namespace KnihovnyCz\ILS\Driver;

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
class Aleph extends AlephBase
{

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
     * @throws DateException
     * @throws ILSException
     * @return array         On success, an associative array with the following
     * keys: id, availability (boolean), status, location, reserve, callnumber,
     * duedate, number, barcode.
     *
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
            $item_status         = (string)$item->{'z30-item-status-code'}; // $isc
            // $ipsc:
            $item_process_status = (string)$item->{'z30-item-process-status-code'};
            $sub_library_code    = (string)$item->{'z30-sub-library-code'}; // $slc
            $z30 = $item->z30;
            if ($this->alephTranslator) {
                $item_status = $this->alephTranslator->tab15Translate(
                    $sub_library_code,
                    $item_status,
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
            if ($item_status['opac'] != 'Y') {
                continue;
            }
            $availability = false;
            $collection = (string)$z30->{'z30-collection'};
            $collection_desc = ['desc' => $collection];
            if ($this->alephTranslator) {
                $collection_code = (string)$item->{'z30-collection-code'};
                $collection_desc = $this->alephTranslator->tab40Translate(
                    $collection_code,
                    $sub_library_code
                );
            }
            $requested = false;
            $duedate = '';
            $addLink = false;
            $status = (string)$item->{'status'};
            if (in_array($status, $this->available_statuses)) {
                $availability = true;
            }
            if ($item_status['request'] == 'Y' && $availability == false) {
                $addLink = true;
            }
            if (!empty($patron)) {
                $hold_request = $item->xpath('info[@type="HoldRequest"]/@allowed');
                $addLink = ($hold_request[0] == 'Y');
            }
            $matches = [];
            $dueDateWithStatusRegEx
                = "/([0-9]*\\/[a-zA-Z0-9]*\\/[0-9]*);([a-zA-Z ]*)/";
            $dueDateRegEx = "/([0-9]*\\/[a-zA-Z0-9]*\\/[0-9]*)/";
            if (preg_match($dueDateWithStatusRegEx, $status, $matches)) {
                $duedate = $this->parseDate($matches[1]);
                $requested = (trim($matches[2]) == "Requested");
            } elseif (preg_match($dueDateRegEx, $status, $matches)) {
                $duedate = $this->parseDate($matches[1]);
            } else {
                $duedate = null;
            }
            $item_id = $item->attributes()->href;
            $item_id = substr($item_id, strrpos($item_id, '/') + 1);
            $note    = (string)$z30->{'z30-note-opac'};
            $holding[] = [
                'id'                  => $id,
                'item_id'             => $item_id,
                'availability'        => $availability,
                'availability_status' => (string)$z30->{'z30-item-status'},
                'status'              => (string)$item->{'status'},
                'location'            => (string)$z30->{'z30-sub-library'},
                'reserve'             => 'N',
                'callnumber'          => (string)$z30->{'z30-call-no'},
                'duedate'             => (string)$duedate,
                'number'              => (string)$z30->{'z30-inventory-number'},
                'barcode'             => (string)$z30->{'z30-barcode'},
                'description'         => (string)$z30->{'z30-description'},
                'notes'               => ($note == null) ? null : [$note],
                'is_holdable'         => true,
                'addLink'             => $addLink,
                'holdtype'            => 'hold',
                /* below are optional attributes*/
                'collection'          => (string)$collection,
                'collection_desc'     => (string)$collection_desc['desc'],
                'callnumber_second'   => (string)$z30->{'z30-call-no-2'},
                'sub_lib_desc'        => (string)$item_status['sub_lib_desc'],
                'no_of_loans'         => (string)$z30->{'$no_of_loans'},
                'requested'           => (string)$requested
            ];
        }
        return $holding;
    }

}