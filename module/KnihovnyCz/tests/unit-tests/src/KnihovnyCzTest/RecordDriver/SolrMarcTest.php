<?php
declare(strict_types=1);

/**
 * Class SolrMarcTest
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
 * Class SolrMarcTest
 *
 * @category Knihovny.cz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SolrMarcTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;

    /**
     * Test getStructuredDataFieldArray
     *
     * @throws ReflectionException
     * @return void
     */
    public function testGetStructuredDataFieldArray(): void
    {
        $method = new \ReflectionMethod(
            '\KnihovnyCz\RecordDriver\SolrMarc', 'getStructuredDataFieldArray'
        );
        $method->setAccessible(true);
        $filename = 'records/record1.json';
        $fixture = $this->getJsonFixture($filename, 'KnihovnyCz');
        $record = $this->createDriver($fixture['response']['docs'][0]);
        $expected996 = [
            [
                'b' => '1003423477',
                'c' => '54 K 119668',
                'l' => 'SKLAD V REKONSTRUKCI',
                'r' => 'sklad',
                's' => 'N',
                'n' => '1',
                'w' => '002953486',
                'u' => '000020',
                'j' => 'NKC50',
                't' => 'ABA001.NKC01002931098.NKC50002953486000020',
            ],
            [
                'b' => '1003934167',
                'c' => 'I 580975',
                'l' => 'NÁRODNÍ KONZERVAČNÍ FOND',
                'r' => 'sklad',
                's' => 'O',
                'n' => '5',
                'w' => '002953486',
                'u' => '000010',
                'j' => 'NKC50',
                't' => 'ABA001.NKC01002931098.NKC50002953486000010',
                'a' => '24',
            ],
        ];
        $fields = $method->invokeArgs($record, ['996']);
        $this->assertEquals($expected996, $fields);
        $expected024 = [];
        $fields = $method->invokeArgs($record, ['024']);
        $this->assertEquals($expected024, $fields);

        $record = $this->createDriver($fixture['response']['docs'][1]);
        $expected996 = [
            [
                'a' => '02',
                'b' => '2651725658',
                'c' => '1-334.179',
                'l' => 'Běžný fond',
                'r' => 'Hlavní sklad',
                's' => 'A',
                'n' => '4',
                'w' => '001236141',
                'u' => '000010',
                'j' => 'SVK50',
                't' => 'OLA001.SVK01001217387.SVK50001236141000010',
                'm' => 'BOOK',
            ],
        ];
        $fields = $method->invokeArgs($record, ['996']);
        $this->assertEquals($expected996, $fields);
    }

    /**
     * Create new record driver
     *
     * @param array $fieldData Field data from SOLR response
     *
     * @return \KnihovnyCz\RecordDriver\SolrMarc
     */
    protected function createDriver(array $fieldData): \KnihovnyCz\RecordDriver\SolrMarc
    {
        $config = new \Laminas\Config\Config([]);
        $record = new \KnihovnyCz\RecordDriver\SolrMarc($config);
        $record->setRawData($fieldData);
        return $record;
    }
}
