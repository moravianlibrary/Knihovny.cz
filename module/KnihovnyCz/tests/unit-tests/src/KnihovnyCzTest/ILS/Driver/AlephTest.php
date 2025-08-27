<?php

namespace KnihovnyCzTest\ILS\Driver;

use InvalidArgumentException;
use KnihovnyCz\ILS\Driver\Aleph;
use Laminas\Http\Response as HttpResponse;

/**
 * Class AlephTest
 *
 * @category Knihovny.cz
 * @package  Tests
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AlephTest extends \VuFindTest\ILS\Driver\AlephTest
{
    use \VuFindTest\Feature\TranslatorTrait;

    /**
     * ILS driver
     *
     * @var \KnihovnyCz\ILS\Driver\Aleph
     */
    protected $driver;

    /**
     * Test getMyBlocks
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetMyBlocks(): void
    {
        $this->configureDriver();
        $this->driver->setSource('aaa');
        $translator = $this->getMockTranslator(
            [
                'ILSMessages' => [
                    'aaa.block_12' => '12 - Blok 12',
                    'aaa.block_34' => '34 - Blok 34',
                ],
            ],
            'cs'
        );
        $this->driver->setTranslator($translator);
        $this->mockResponse('bor-info.xml');
        $expected = [
            [
                'id' => 'block_34',
                'label' => '34 - Blok 34',
                'updated' => '12-12-2024',
            ],
            [
                'id' => 'block_12',
                'label' => '12 - Blok 12',
                'updated' => '12-12-2024',
            ],
        ];
        $blocks = $this->driver->getMyBlocks(['id' => 'NK1234567']);
        $this->assertIsArray($blocks);
        $this->assertEquals($expected, $blocks);
        $blocksFromAlephConfig = $this->getDefaultConfig();
        $blocksFromAlephConfig['ProfileBlocks'] = [
            'showAlephLabel' => 'true',
            'enabled' => 'true',
        ];
        $this->configureDriver($blocksFromAlephConfig);
        $this->mockResponse('patron-blocks.xml');
        $expected = [
            [
                'label' => '34 - Nezaplacená pokuta, registrace nebo MVS TEST',
            ],
        ];
        $blocks = $this->driver->getMyBlocks(['id' => 'NK1234567']);
        $this->assertIsArray($blocks);
        $this->assertEquals($expected, $blocks);
        $this->configureDriver($blocksFromAlephConfig);
        $this->mockResponse('patron-blocks-multi.xml');
        $expected = [
            [
                'label' => '12 - Vzkaz pro čtenáře',
            ],
            [
                'label' => '15 - Ohlášená ztráta čtenářského průkazu',
            ],
        ];
        $blocks = $this->driver->getMyBlocks(['id' => 'NK1234567']);
        $this->assertEquals($expected, $blocks);
    }

    /**
     * Test getMyFines
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetMyFines(): void
    {
        $config = $this->getDefaultConfig();
        $config['Catalog']['showAccruingFines'] = 'true';
        $this->configureDriver($config);
        $expected = [
            [
                'title' => '',
                'barcode' => '',
                'amount' => -40000.0,
                'transactiondate' => '08-08-2024',
                'checkout' => '08-08-2024',
                'balance' => -40000.0,
                'id' => null,
                'printLink' => 'test',
                'fine' => 'B Online registrace rocni - AU',
                'transactiontype' => 'K tíži',
            ],
            [
                'title' => 'PHP : kapesní přehled / Lukáš Krejčí',
                'barcode' => '2610276641',
                'amount' => -2800.0,
                'transactiondate' => '11-20-2017',
                'checkout' => '11-09-2017',
                'balance' => -2800.0,
                'id' => '000812790',
                'printLink' => 'test',
                'fine' => 'loan_fine',
            ],
            [
                'title'
                    => 'Algoritmy : základní konstrukce v příkladech a jejich vizualizace / Eva Milková ... [et al.]',
                'barcode' => '2610453364',
                'amount' => -3000.0,
                'transactiondate' => '11-20-2017',
                'checkout' => '11-09-2017',
                'balance' => -3000.0,
                'id' => '001156544',
                'printLink' => 'test',
                'fine' => 'loan_fine',
            ],
        ];
        $this->mockResponse(['cash.xml', 'loans.xml']);
        $patron = ['id' => 'TESTUSER'];
        $loans = $this->driver->getMyFines($patron);
        $this->assertEquals($expected, $loans);
    }

    /**
     * Test getHolding with patron
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetHoldingWithPatron(): void
    {
        $config = $this->getDefaultConfig();
        $this->configureDriver($config);
        $this->mockResponse(['items_hold_with_patron.xml']);
        $expected = $this->getExpectedGetHolding(true);
        $patron = ['id' => 'TESTUSER'];
        $items = $this->driver->getHolding('LIB01-0000001', $patron);
        $this->assertEquals($expected, $items);
    }

    /**
     * Test getHolding without patron
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetHoldingWithoutPatron(): void
    {
        $config = $this->getDefaultConfig();
        $this->configureDriver($config);
        $this->mockResponse(['items_hold_without_patron.xml']);
        $expected = $this->getExpectedGetHolding(false);
        $items = $this->driver->getHolding('LIB01-0000001');
        $this->assertEquals($expected, $items);
    }

    /**
     * Test getHolding with one item for short loan and patron
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetHoldingShortLoanWithPatron(): void
    {
        $config = $this->getDefaultConfig();
        $this->configureDriver($config);
        $this->mockResponse(['items_shortloan_with_patron.xml']);
        $patron = ['id' => 'TESTUSER'];
        $items = $this->driver->getHolding('LIB01-0000002', $patron);
        $expected = [
            'holdings' => [
                0 => [
                    'id' => 'LIB01-0000002',
                    'item_id' => 'LIB50000000002000010',
                    'holdtype' => 'shortloan',
                    'availability' => false,
                    'availability_status' => '6th Floor - at the desk',
                    'status' => 'On Shelf',
                    'location' => 'Keys',
                    'reserve' => 'N',
                    'callnumber' => 'Týmová studovna 7.p',
                    'number' => '',
                    'barcode' => 'S732',
                    'description' => 'Týmová studovna 7.p',
                    'item_notes' => null,
                    'is_holdable' => true,
                    'addLink' => true,
                    'linkText' => 'Reserve',
                    'collection' => '',
                    'collection_desc' => '',
                    'callnumber_second' => 'Týmová studovna 7.p',
                    'sub_lib_desc' => 'Keys',
                    'no_of_loans' => '',
                    'requested' => '',
                ],
            ],
            'filters' => [],
        ];
        $this->assertEquals($expected, $items);
    }

    /**
     * Test getHolding with two checked out items and patron
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetHoldingCheckedoutWithPatronInEnglish()
    {
        $config = $this->getDefaultConfig();
        $this->configureDriver($config);
        $this->mockResponse(['items_hold_checkedout_with_patron_eng.xml']);
        $patron = ['id' => 'TESTUSER'];
        $items = $this->driver->getHolding('LIB01-0000003', $patron);
        $expected = $this->getExpectedGetHoldingCheckedoutWithPatron();
        $this->assertEquals($expected, $items);
    }

    /**
     * Test getHolding with two checked out items and patron
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetHoldingCheckedoutWithPatronInCzech()
    {
        $config = $this->getDefaultConfig();
        $this->configureDriver($config);
        $this->mockResponse(['items_hold_checkedout_with_patron_cze.xml']);
        $patron = ['id' => 'TESTUSER'];
        $items = $this->driver->getHolding('LIB01-0000003', $patron);
        $expected = $this->getExpectedGetHoldingCheckedoutWithPatron();
        $expected['holdings'][1]['status'] = 'holding_due_date 08-11-2025 ;  Požadováno';
        $this->assertEquals($expected, $items);
    }

    /**
     * Return expected return value for testing checked out holdings.
     *
     * @return array
     */
    protected function getExpectedGetHoldingCheckedoutWithPatron()
    {
        return [
            'holdings' => [
                0 => [
                    'id' => 'LIB01-0000003',
                    'item_id' => 'LIB50000000003000010',
                    'holdtype' => 'hold',
                    'availability' => false,
                    'availability_status' => 'Reference only',
                    'status' => 'holding_due_date 08-11-2025',
                    'location' => 'MZK?',
                    'reserve' => 'N',
                    'callnumber' => '1-1544.099',
                    'number' => '2611001358',
                    'barcode' => '2611001358',
                    'description' => '',
                    'item_notes' => null,
                    'is_holdable' => true,
                    'addLink' => false,
                    'linkText' => 'Reserve',
                    'collection' => 'Stock / within 1 hour',
                    'collection_desc' => 'Stock / within 1 hour',
                    'callnumber_second' => '',
                    'sub_lib_desc' => 'MZK?',
                    'no_of_loans' => '',
                    'requested' => '',
                ],
                1 => [
                    'id' => 'LIB01-0000003',
                    'item_id' => 'LIB50000000003000020',
                    'holdtype' => 'hold',
                    'availability' => false,
                    'availability_status' => 'Month',
                    'status' => 'holding_due_date 08-11-2025 ;  Requested',
                    'location' => 'MZK?',
                    'reserve' => 'N',
                    'callnumber' => '1-1544.099',
                    'number' => '2611001359',
                    'barcode' => '2611001359',
                    'description' => '',
                    'item_notes' => null,
                    'is_holdable' => true,
                    'addLink' => false,
                    'linkText' => 'Reserve',
                    'collection' => 'Stock / within 1 hour',
                    'collection_desc' => 'Stock / within 1 hour',
                    'callnumber_second' => '',
                    'sub_lib_desc' => 'MZK?',
                    'no_of_loans' => '',
                    'requested' => '',
                ],
            ],
            'filters' => [],
        ];
    }

    /**
     * Return expected return value for testGetHolding.
     *
     * @param bool $addLink value for attribute addLink
     *
     * @return array
     */
    protected function getExpectedGetHolding(bool $addLink)
    {
        return [
            'holdings' => [
                0 => [
                    'id' => 'LIB01-0000001',
                    'item_id' => 'LIB50000000001000010',
                    'holdtype' => 'hold',
                    'availability' => false,
                    'availability_status' => 'Reference only',
                    'status' => 'On Shelf',
                    'location' => 'MZK?',
                    'reserve' => 'N',
                    'callnumber' => 'S-1546.440',
                    'number' => '2611006762',
                    'barcode' => '2611006762',
                    'description' => '',
                    'item_notes' => null,
                    'is_holdable' => true,
                    'addLink' => $addLink,
                    'linkText' => 'Reserve',
                    'collection' => 'Stock / within 24 hours',
                    'collection_desc' => 'Stock / within 24 hours',
                    'callnumber_second' => '',
                    'sub_lib_desc' => 'MZK?',
                    'no_of_loans' => '',
                    'requested' => '',
                ],
            ],
            'filters' => [],
        ];
    }

    /**
     * Load NCIP response from file
     *
     * @param string $filename File name
     *
     * @return HttpResponse
     *
     * @throws \ReflectionException
     */
    protected function loadResponse($filename): HttpResponse
    {
        $file = realpath(
            __DIR__ .
            '/../../../../../../tests/fixtures/aleph/' . $filename
        );
        if (!is_string($file) || !file_exists($file) || !is_readable($file)) {
            throw new InvalidArgumentException(
                sprintf('Unable to load fixture file: %s ', $file)
            );
        }
        $response = file_get_contents($file);
        if ($response === false) {
            throw new \Exception('Could not read file ' . $file);
        }
        return HttpResponse::fromString($response);
    }

    /**
     * Configure driver for test case
     *
     * @param array|null $config ILS driver configuration
     *
     * @return void
     */
    protected function configureDriver($config = null)
    {
        $this->driver = new Aleph(new \VuFind\Date\Converter());
        $this->driver->setConfig($config ?? $this->getDefaultConfig());
        $this->driver->init();
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    protected function getDefaultConfig(): array
    {
        return [
            'Catalog' => [
                'host' => 'https://aleph.test.example',
                'bib' => 'LIB01',
                'useradm' => 'LIB50',
                'admlib' => 'LIB50',
                'dlfport' => '8991',
                'available_statuses' => 'Půjčené,Volné',
                'wwwuser' => 'wwwuser',
                'wwwpasswd' => 'wwwpasswd',
            ],
            'sublibadm' => [
                'LIB' => 'LIB50',
            ],
        ];
    }
}
