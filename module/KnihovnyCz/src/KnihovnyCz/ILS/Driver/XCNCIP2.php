<?php

namespace KnihovnyCz\ILS\Driver;

use VuFind\Date\DateException;
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
    // 'loan' is for Verbis
    protected $holdRequestTypes = ['hold', 'recall', 'r', 'z', 'loan'];

    /**
     * Lowercased circulation statuses we consider not be holdable
     *
     * @var string[]
     */
    protected $notHoldableStatuses = [
        'circulation status undefined', 'lost',
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
    public function getConfig($function, $params = [])
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
     * Get Hold Type
     *
     * @param string $status Status string from CirculationStatus NCIP element
     *
     * @return string Hold type: 'Hold', 'Recall', 'Estimate', 'Loan' are some of
     * possible values
     */
    protected function getHoldType(string $status)
    {
        // If it work, we keep it hardcoded. If not, we should use 'Estimate' for
        // some of ILSs
        return 'Hold';
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
     * @throws DateException
     * @throws ILSException
     * @return array         On success, an associative array with the following
     * keys: id, availability (boolean), status, location, reserve, callnumber,
     * duedate, number, barcode.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getHolding($id, array $patron = null, array $options = [])
    {
        $holdings = parent::getHolding($id, $patron, $options);
        $holdings = array_map(
            function ($holding) {
                if ($holding['status'] !== 'On Loan') {
                    $holding['duedate'] =  null;
                }
                $status = $holding['status'];
                if (in_array($status, $this->itemUseRestrictionTypesForStatus)) {
                    $holding['availability_status'] = $this->translate('HoldingStatus::' . $status);
                    if ($status === 'In Library Use Only') {
                        $holding['status'] = 'Available On Shelf';
                    }
                }
                return $holding;
            },
            $holdings
        );
        return $holdings;
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
            '',
            '',
            $patron['patronAgencyId'],
            $extras,
            $patron['id']
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
                $bibId = !empty($bibId) ? (string)$bibId[0] : '1';
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
            'ns2',
            'https://ncip.knihovny.cz/ILSDI/ncip/2015/extensions'
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

    /**
     * Get Status By Item ID
     *
     * This is responsible for retrieving the status information of a certain
     * item.
     *
     * @param string $itemId The item id to retrieve the holdings for
     *
     * @throws ILSException
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatusByItemId($itemId)
    {
        $desiredParts = [
            'Bibliographic Description',
            'Circulation Status',
            'Item Description',
            'Item Use Restriction Type',
            'Location',
        ];

        $itemRequest = $this->getLookupItemRequest($itemId, null, $desiredParts);
        $itemResponse = $this->sendRequest($itemRequest);
        $bibId = $itemResponse->xpath(
            'ns1:LookupItemResponse/ns1:ItemOptionalFields/' .
            'ns1:BibliographicDescription/ns1:BibliographicItemId/' .
            'ns1:BibliographicItemIdentifier' .
            ' | ' .
            'ns1:LookupItemResponse/ns1:ItemOptionalFields/' .
            'ns1:BibliographicDescription/ns1:BibliographicRecordId/' .
            'ns1:BibliographicRecordIdentifier'
        );
        $bibId = !empty($bibId) ? (string)$bibId[0] : '1';

        $status = $itemResponse->xpath(
            'ns1:LookupItemResponse/ns1:ItemOptionalFields/ns1:CirculationStatus'
        );
        $status = !empty($status) ? (string)$status[0] : '';

        $locations = $itemResponse->xpath(
            'ns1:LookupItemResponse/ns1:ItemOptionalFields/ns1:Location/' .
            'ns1:LocationName/ns1:LocationNameInstance'
        );
        $locations = $locations ?: [];
        [$location, ] = $this->parseLocationInstance($locations);

        $itemCallNo = $itemResponse->xpath(
            'ns1:LookupItemResponse/ns1:ItemOptionalFields/ns1:ItemDescription/' .
            'ns1:CallNumber'
        );
        $itemCallNo = !empty($itemCallNo) ? (string)$itemCallNo[0] : null;
        return [
            'id' => $bibId,
            'item_id' => $itemId,
            'availability' => $this->isAvailable($status),
            'status' => $status,
            'location' => $location,
            'callnumber' => $itemCallNo,
        ];
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
        $profile = parent::getMyProfile($patron);
        $patron = $this->patronLogin(
            $patron['cat_username'],
            $patron['cat_password']
        );
        if (isset($patron['email'])) {
            $profile['email'] = $patron['email'];
        }
        return $profile;
    }

    /**
     * Get Default Pick Up Location
     *
     * Returns the default pick up location set in HorizonXMLAPI.ini
     *
     * @param array $patron      Patron information returned by the patronLogin
     * method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing a hold; contains most of the same values passed to
     * placeHold, minus the patron data.  May be used to limit the pickup options
     * or may be ignored.
     *
     * @return string A location ID
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultPickUpLocation($patron, $holdDetails = null)
    {
        return !empty($this->pickupLocations)
            ? $this->pickupLocations[0]['locationID']
            : false;
    }

    /**
     * Check NextItemToken for emptiness
     *
     * @param \SimpleXMLElement[] $nextItemToken Next item token elements from NCIP
     * Response
     *
     * @return bool
     */
    protected function isNextItemTokenEmpty(array $nextItemToken): bool
    {
        return !empty($nextItemToken) && !empty((string)$nextItemToken[0]);
    }
}
