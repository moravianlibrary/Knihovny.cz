<?php

/**
 * Class XCNCIP2
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
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\ILS\Driver;

use VuFind\Exception\ILS as ILSException;

/**
 * Class XCNCIP2
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class XCNCIP2 extends \VuFind\ILS\Driver\XCNCIP2
{
    /**
     * Lowercased request type strings identifying holds
     *
     * @var string[]
     */
    // 'r' and 'z' are specific for ARL, would be better to make them to fix it
    protected $holdRequestTypes = ['hold', 'recall', 'r', 'z'];

    /**
     * Lowercased circulation statuses we consider not be holdable
     *
     * @var string[]
     */
    protected $notHoldableStatuses = [
        'circulation status undefined', 'lost'
    ];

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
        // Needed to make ARL NCIP respond correctly on LookupUser and LookupItem
        $this->schemes['UserIdentifierType']
            = 'http://www.niso.org/ncip/v1_0/imp1/schemes/' .
            'visibleuseridentifiertype/visibleuseridentifiertype.scm';
        $this->schemes['ItemIdentifierType']
            = 'http://www.niso.org/ncip/v1_0/imp1/schemes/' .
            'visibleitemidentifiertype/visibleitemidentifiertype.scm';
        parent::init();
    }

    /**
     * Public Function which specifies renew, hold and cancel settings.
     *
     * @param string $function The name of the feature to be checked
     * @param array  $params   Optional feature-specific parameters (array)
     *
     * @return array An array with key-value pairs.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConfig($function, $params = null)
    {
        if ($function == 'getMyTransactionHistory') {
            // See https://docs.google.com/document/d/1yzXOjSzl56jK_K5A9TgYpz4ddPsIHEvnrzUdf0dO8eQ/edit#heading=h.ac9ql1xl54r7
            return [
                'page_size' => [10],
                'default_page_size' => 10,
                'max_results' => 10,
            ];
        }
        return parent::getConfig($function, $params);
    }

    /**
     * Get Patron Loan History
     *
     * This is responsible for retrieving all historic transactions for a specific
     * patron.
     *
     * @param array $patron The patron array from patronLogin
     * @param array $params Parameters
     *
     * @return mixed        Array of the patron's historic transactions on success.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMyTransactionHistory($patron, $params)
    {
        $extras = [
            '<ns1:Ext><ns2:HistoryDesired><ns2:Page>' .
            $this->parsePage($params['page'] ?? 1) .
            '</ns2:Page></ns2:HistoryDesired></ns1:Ext>',
        ];
        $request = $this->getLookupUserRequest(
            '', '', $patron['patronAgencyId'], $extras, $patron['id']
        );

        $response = $this->sendRequest($request);
        $items = $response->xpath(
            'ns1:LookupUserResponse/ns1:Ext/ns2:LoanedItemsHistory/ns1:LoanedItem'
        );
        $items = empty($items) ? [] : $items;
        // Are we able to use this to limit pagination?
        $totalPages = $response->xpath(
            'ns1:LookupUserResponse/ns1:Ext/ns2:LoanedItemsHistory/ns2:LastPage'
        );

        $retVal = [];

        foreach ($items as $item) {
            $this->registerNamespaceFor($item);

            $title = $item->xpath('ns1:Title');
            $itemId = $item->xpath('ns1:ItemId/ns1:ItemIdentifierValue');
            $itemId = !empty($itemId) ? (string)$itemId[0] : '';
            $itemIdType = $item->xpath('ns1:ItemId/ns1:ItemIdentifierType');
            $itemIdType = !empty($itemIdType) ? (string)$itemIdType[0] : '';

            $dueDate = $item->xpath('ns1:DateDue');
            $dueDate = $this->displayDate(
                !empty($dueDate) ? (string)$dueDate[0] : ''
            );

            $bibId = $item->xpath(
                'ns1:Ext/ns1:BibliographicDescription/' .
                'ns1:BibliographicRecordId/ns1:BibliographicRecordIdentifier' .
                ' | ' .
                'ns1:Ext/ns1:BibliographicDescription/' .
                'ns1:BibliographicItemId/ns1:BibliographicItemIdentifier'
            );
            $itemAgencyId = $item->xpath(
                'ns1:Ext/ns1:BibliographicDescription/' .
                'ns1:BibliographicRecordId/ns1:AgencyId' .
                ' | ' .
                'ns1:ItemId/ns1:AgencyId'
            );

            $itemAgencyId = !empty($itemAgencyId) ? (string)$itemAgencyId[0] : null;
            $bibId = !empty($bibId) ? (string)$bibId[0] : null;
            if ($bibId === null || $itemAgencyId === null) {
                $itemRequest = $this->getLookupItemRequest($itemId, $itemIdType);
                /**
                 * Item response
                 *
                 * @var \SimpleXMLElement
                 */
                $itemResponse = $this->sendRequest($itemRequest);
            }
            if ($bibId === null) {
                /* @phpstan-ignore-next-line */
                $bibId = $itemResponse->xpath(
                    'ns1:LookupItemResponse/ns1:ItemOptionalFields/' .
                    'ns1:BibliographicDescription/ns1:BibliographicItemId/' .
                    'ns1:BibliographicItemIdentifier' .
                    ' | ' .
                    'ns1:LookupItemResponse/ns1:ItemOptionalFields/' .
                    'ns1:BibliographicDescription/ns1:BibliographicRecordId/' .
                    'ns1:BibliographicRecordIdentifier'
                );
                // Hack to account for bibs from other non-local institutions
                // temporarily until consortial functionality is enabled.
                $bibId = !empty($bibId) ? (string)$bibId[0] : "1";
            }
            if ($itemAgencyId === null) {
                /* @phpstan-ignore-next-line */
                $itemAgencyId = $itemResponse->xpath(
                    'ns1:LookupItemResponse/ns1:ItemOptionalFields/' .
                    'ns1:BibliographicDescription/ns1:BibliographicRecordId/' .
                    'ns1:AgencyId' .
                    ' | ' .
                    'ns1:LookupItemResponse/ns1:ItemId/ns1:AgencyId'
                );
                $itemAgencyId = !empty($itemAgencyId)
                    ? (string)$itemAgencyId[0] : null;
            }

            $retVal[] = [
                'id' => $bibId,
                'item_id' => $itemId,
                'item_agency_id' => $itemAgencyId,
                'patronAgencyId' => $patron['patronAgencyId'],
                'barcode' => ($itemIdType === 'Barcode')
                    ? $itemId : '',
                'title' => !empty($title) ? (string)$title[0] : '',
                'dueDate' => $dueDate,
            ];
        }

        return [
            'count' => count($retVal),
            'transactions' => $retVal,
        ];
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
        if ($method == 'getMyTransactionHistory') {
            return isset($this->config['Catalog']['transactionsHistory']);
        }
        return is_callable([$this, $method]);
    }

    /**
     * Helper method for creating XML header and main element start
     *
     * @return string
     */
    protected function getNCIPMessageStart()
    {
        // XSD file for extension could be found here:
        // https://github.com/eXtensibleCatalog/NCIP2-Toolkit/blob/master/core/trunk/binding/ilsdiv1_1/src/main/xsd/ncip_v2_02_ils-di_extensions.xsd
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<ns1:NCIPMessage xmlns:ns1="http://www.niso.org/2008/ncip" ' .
            'xmlns:ns2="https://ncip.knihovny.cz/ILSDI/ncip/2015/extensions" ' .
            'ns1:version="http://www.niso.org/schemas/ncip/v2_02/ncip_v2_02.xsd">';
    }

    /**
     * Register namespace(s) for an XML element/tree
     *
     * @param \SimpleXMLElement $element Element to register namespace for
     *
     * @return void
     */
    protected function registerNamespaceFor(\SimpleXMLElement $element)
    {
        $element->registerXPathNamespace('ns1', 'http://www.niso.org/2008/ncip');
        $element->registerXPathNamespace(
            'ns2', 'https://ncip.knihovny.cz/ILSDI/ncip/2015/extensions'
        );
    }

    /**
     * Parse page from params to string usable for XML
     *
     * @param int|string $page Page from params
     *
     * @return string
     */
    protected function parsePage($page): string
    {
        $returnPage = is_int($page) ? $page : 1;
        $returnPage = is_string($page) && is_numeric($page)
            ? (int)$page : $returnPage;
        $returnPage = $returnPage < 1 ? 1 : $returnPage;
        return (string)$returnPage;
    }
}