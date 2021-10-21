<?php
declare(strict_types=1);

/**
 * Class SolrLocalTest
 *
 * PHP version 7
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
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

/**
 * Class SolrLocalTest
 *
 * @category Knihovny.cz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SolrLocalTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;

    /**
     * Test getOfflineHoldings method
     */
    public function testGetOfflineHoldings(): void
    {
        $expectedItems = [
            [
                'item_id' => 'AAA001I-03920/2005001',
                'callnumber' => 'UK-88096/1',
                'location' => 'Brno - sklad',
                'callnumber_second' => 'vlevo dole',
                'description' => '',
                'notes' => '',
                'year' => '1943',
                'volume' => 'XXIII',
                'issue' => '2',
                'status' => 'A',
                'collection_desc' => 'ÚK-sklad',
                'agency_id' => 'AAA001',
                'copy_number' => '1',
            ], [
                'item_id' => 'J-1/2020',
                'callnumber' => '',
                'location' => 'Brno - sklad',
                'callnumber_second' => '',
                'description' => '',
                'notes' => '',
                'year' => '',
                'volume' => '',
                'issue' => '',
                'status' => 'A',
                'collection_desc' => 'ÚK-sklad',
                'agency_id' => '',
                'copy_number' => '',
            ],
        ];

        $expected = [
            'holdings' => [
                [
                    'location' => 'default',
                    'items' => $expectedItems,
                ],
            ],
            'filters' => [
               'year' => [
                   '' => '',
                   '1943' => '1943',
               ],
               'volume' => [
                   '' => '',
                   'XXIII' => 'XXIII',
               ],
            ],
        ];
        $filename = 'records/offlineHoldings.json';
        $fixture = $this->getJsonFixture($filename, 'KnihovnyCz');
        $record = $this->createDriver($fixture['response']['docs'][0]);
        $holdings = $record->getOfflineHoldings();
        $this->assertEquals($expected, $holdings);
        $filename = 'records/offlineHoldingsEmpty.json';
        $fixture = $this->getJsonFixture($filename, 'KnihovnyCz');
        $record = $this->createDriver($fixture['response']['docs'][0]);
        $holdings = $record->getOfflineHoldings();
        $this->assertEquals([], $holdings);
    }

    /**
     * Test hasOfflineHoldings method
     */
    public function testHasOfflineHoldings(): void
    {
        $filename = 'records/offlineHoldings.json';
        $fixture = $this->getJsonFixture($filename, 'KnihovnyCz');
        $record = $this->createDriver($fixture['response']['docs'][0]);
        $this->assertTrue($record->hasOfflineHoldings());
        $filename = 'records/offlineHoldingsEmpty.json';
        $fixture = $this->getJsonFixture($filename, 'KnihovnyCz');
        $record = $this->createDriver($fixture['response']['docs'][0]);
        $this->assertFalse($record->hasOfflineHoldings());
    }

    /**
     * Create new record driver
     *
     * @param array $fieldData Field data from SOLR response
     *
     * @return \KnihovnyCz\RecordDriver\SolrLocal
     */
    protected function createDriver(array $fieldData): \KnihovnyCz\RecordDriver\SolrLocal
    {
        $config = new \Laminas\Config\Config([]);
        $record = new \KnihovnyCz\RecordDriver\SolrLocal($config);
        $record->setRawData($fieldData);
        return $record;
    }
}
