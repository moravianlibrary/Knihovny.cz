<?php

declare(strict_types=1);

namespace KnihovnyCzTest\RecordDriver;

/**
 * Class SolrAuthorityTest
 *
 * @category Knihovny.cz
 * @package  Tests
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SolrAuthorityTest extends \PHPUnit\Framework\TestCase
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
            '\KnihovnyCz\RecordDriver\SolrMarc',
            'getStructuredDataFieldArray'
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
                'ind1' => ' ',
                'ind2' => ' ',
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
                'ind1' => ' ',
                'ind2' => ' ',
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
                'ind1' => ' ',
                'ind2' => ' ',
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
     * @return \KnihovnyCz\RecordDriver\SolrAuthority
     */
    protected function createDriver(array $fieldData): \KnihovnyCz\RecordDriver\SolrAuthority
    {
        $config = new \Laminas\Config\Config([]);
        $record = new \KnihovnyCz\RecordDriver\SolrAuthority($config);
        $record->setRawData($fieldData);
        return $record;
    }
}
