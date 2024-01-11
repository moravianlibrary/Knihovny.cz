<?php

declare(strict_types=1);

namespace KnihovnyCz\ILS\Driver;

use VuFind\Exception\ILS as ILSException;

/**
 * Class KohaRest
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class KohaRest extends \VuFind\ILS\Driver\KohaRest
{
    /**
     * Checkout statuses
     *
     * @var array
     */
    protected $statuses = [
        'Charged' => 'On Loan',
        'On Shelf' => 'Available On Shelf',
        'In Transit' => 'In Transit Between Library Locations',
        'On Hold' => 'Available For Pickup',
    ];

    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $username The patron username
     * @param string $password The patron password
     *
     * @throws ILSException
     * @return mixed           Associative array of patron info on successful login,
     * null on unsuccessful login.
     */
    public function patronLogin($username, $password)
    {
        $result = $this->makeRequest(['v1', 'patrons', $username]);
        if (200 !== $result['code']) {
            throw new ILSException('Problem with Koha REST API.');
        }

        $data = $result['data'];
        return [
            'id' => $data['patron_id'],
            'firstname' => $data['firstname'],
            'lastname' => $data['surname'],
            'cat_username' => $username,
            'cat_password' => $password,
            'email' => $data['email'],
            'major' => null,
            'college' => $data['category_id'],
            'home_library' => $data['library_id'],
        ];
    }

    /**
     * Get Pick Up Locations
     *
     * This is responsible for gettting a list of valid library locations for
     * holds / recall retrieval
     *
     * @param array $patron      Patron information returned by the patronLogin
     * method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing or editing a hold. When placing a hold, it contains
     * most of the same values passed to placeHold, minus the patron data. When
     * editing a hold it contains all the hold information returned by getMyHolds.
     * May be used to limit the pickup options or may be ignored. The driver must
     * not add new options to the return array based on this data or other areas of
     * VuFind may behave incorrectly.
     *
     * @throws ILSException
     * @return array        An array of associative arrays with locationID and
     * locationDisplay keys
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPickUpLocations($patron = false, $holdDetails = null)
    {
        $bibId = $holdDetails['id'] ?? null;
        $itemId = $holdDetails['item_id'] ?? false;

        $requestType
            = array_key_exists('StorageRetrievalRequest', $holdDetails ?? [])
            ? 'StorageRetrievalRequests' : 'Holds';
        $availableLocations = [];
        if ($bibId && 'Holds' === $requestType) {
            // Collect library codes that are to be included
            $level = !empty($holdDetails['level']) ? $holdDetails['level'] : 'title';
            if ('copy' === $level && false === $itemId) {
                return [];
            }
            if ('copy' === $level) {
                $result = $this->makeRequest(
                    [
                        'path' => [
                            'v1', 'items', $itemId, 'pickup_locations',
                        ],
                        'query' => [
                            'patron_id' => (int)$patron['id'],
                        ],
                    ]
                );
                if (empty($result['data'])) {
                    return [];
                }
                $availableLocations = $result['data'];
            } else {
                $result = $this->makeRequest(
                    [
                        'path' => [
                            'v1', 'biblios', $bibId, 'pickup_locations',
                        ],
                        'query' => [
                            'patron_id' => (int)$patron['id'],
                        ],
                    ]
                );
                if (empty($result['data'])) {
                    return [];
                }
                $availableLocations = $result['data'];
            }
        }

        $locations = [];
        foreach ($availableLocations as $library) {
            $locations[] = [
                'locationID' => $library['library_id'],
                'locationDisplay' => $library['name'],
            ];
        }

        // Do we need to sort pickup locations? If the setting is false, don't
        // bother doing any more work. If it's not set at all, default to
        // alphabetical order.
        $orderSetting = $this->config['Holds']['pickUpLocationOrder'] ?? 'default';
        if (count($locations) > 1 && !empty($orderSetting)) {
            $locationOrder = $orderSetting === 'default'
                ? [] : array_flip(explode(':', $orderSetting));
            $sortFunction = function ($a, $b) use ($locationOrder) {
                $aLoc = $a['locationID'];
                $bLoc = $b['locationID'];
                if (isset($locationOrder[$aLoc])) {
                    if (isset($locationOrder[$bLoc])) {
                        return $locationOrder[$aLoc] - $locationOrder[$bLoc];
                    }
                    return -1;
                }
                if (isset($locationOrder[$bLoc])) {
                    return 1;
                }
                return $this->getSorter()->compare(
                    $a['locationDisplay'],
                    $b['locationDisplay']
                );
            };
            usort($locations, $sortFunction);
        }

        return $locations;
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
        $item = $this->getItem((int)$id);
        $statuses = $this->getItemStatusesForBiblio($item['biblio_id']);
        $statuses = array_filter(
            $statuses,
            function ($item) use ($id) {
                return $item['item_id'] === (int)$id;
            }
        );
        if (empty($statuses)) {
            return [];
        }
        $status = array_shift($statuses);

        return [
            'id' => $item['biblio_id'],
            'item_id' => $item['item_id'],
            'availability' => $status['availability'],
            'status' => $item['not_for_loan_status'] ? 'Not For Loan' : ($this->statuses[$status['status']] ?? ''),
            'location' => $this->getItemLocationName($item),
            'callnumber' => $item['callnumber'],
            'duedate' => $status['duedate'] ?? null,
        ];
    }

    /**
     * Status item sort function
     *
     * @param array $a First status record to compare
     * @param array $b Second status record to compare
     *
     * @return int
     */
    protected function statusSortFunction($a, $b)
    {
        $result = $this->getSorter()->compare($a['location'], $b['location']);

        if (0 === $result && $this->sortItemsBySerialIssue) {
            $result = strnatcmp($a['number'] ?? '', $b['number'] ?? '');
        }

        if (0 === $result) {
            $result = $a['sort'] - $b['sort'];
        }
        return $result;
    }
}
