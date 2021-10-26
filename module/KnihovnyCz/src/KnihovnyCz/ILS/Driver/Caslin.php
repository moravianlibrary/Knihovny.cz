<?php
declare(strict_types=1);

/**
 * Class Caslin
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2021.
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\ILS\Driver;

use VuFind\Exception\ILS as ILSException;
use VuFind\ILS\Driver\AbstractBase;
use VuFind\Record\Loader as RecordLoader;

/**
 * Class Caslin
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Caslin extends AbstractBase implements \Laminas\Log\LoggerAwareInterface,
    \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Record loader
     *
     * @var RecordLoader
     */
    protected RecordLoader $recordLoader;

    /**
     * Domain used to translate messages from ILS
     *
     * @var string
     */
    protected string $translationDomain = 'Sigla';

    /**
     * Constructor
     *
     * @param RecordLoader $recordLoader Record loader
     */
    public function __construct(RecordLoader $recordLoader)
    {
        $this->recordLoader = $recordLoader;
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
        try {
            /**
             * SolrLocal record driver
             *
             * @var \KnihovnyCz\RecordDriver\SolrLocal
             */
            $record = $this->recordLoader->load('caslin.' . $id);
        } catch (\Exception $exception) {
            $this->logError('Could not load record id ' . $id);
            return [];
        }

        $holdings = $record->getOfflineHoldings();
        $holdings = $holdings['holdings'][0]['items'] ?? [];

        $result = [];
        foreach ($holdings as $holding) {
            $sigla = $holding['location'];
            $status = empty($holding['catalog_link'])
                ? $holding['copy_number']
                : sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    $holding['catalog_link'],
                    $this->translate('caslin_tab_link')
                );
            $result[] = [
                'number' => $holding['copy_number'],
                'status' => $status,
                'location' => $this->translateMessage($sigla),
            ];
        }
        return $result;
    }

    /**
     * Translate a message from ILS
     *
     * @param string $message Message to be translated
     *
     * @return string
     */
    protected function translateMessage(string $message): string
    {
        return $this->translate($this->translationDomain . '::' . $message);
    }

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @throws \VuFind\Exception\ILS
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatus($id)
    {
        return $this->getHolding($id);
    }

    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     *
     * @param array $ids The array of record ids to retrieve the status for
     *
     * @throws \VuFind\Exception\ILS
     * @return array     An array of getStatus() return values on success.
     */
    public function getStatuses($ids)
    {
        $statuses = [];
        foreach ($ids as $id) {
            $statuses[$id] = $this->getStatus($id);
        }
        return $statuses;
    }

    /**
     * Get Purchase History
     *
     * This is responsible for retrieving the acquisitions history data for the
     * specific record (usually recently received issues of a serial).
     *
     * @param string $id The record id to retrieve the info for
     *
     * @throws \VuFind\Exception\ILS
     * @return array     An array with the acquisitions data on success.
     */
    public function getPurchaseHistory($id)
    {
        return [];
    }
}
