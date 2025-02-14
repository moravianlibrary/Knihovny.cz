<?php

namespace KnihovnyCzTest\ILS\Driver;

use InvalidArgumentException;
use KnihovnyCz\ILS\Driver\Aleph;
use Laminas\Http\Client\Adapter\Test as TestAdapter;
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
class AlephTest extends \PHPUnit\Framework\TestCase
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
        $loans = $this->driver->getMyFines([]);
        $this->assertEquals($expected, $loans);
    }

    /**
     * Mock fixture as HTTP client response
     *
     * @param string|array|null $fixture Fixture file
     *
     * @return void
     * @throws InvalidArgumentException Fixture file does not exist
     */
    protected function mockResponse($fixture = null): void
    {
        $adapter = new TestAdapter();
        if (!empty($fixture)) {
            $fixture = (array)$fixture;
            $responseObj = $this->loadResponse($fixture[0]);
            $adapter->setResponse($responseObj);
            array_shift($fixture);
            foreach ($fixture as $f) {
                $responseObj = $this->loadResponse($f);
                $adapter->addResponse($responseObj);
            }
        }

        $service = new \VuFindHttp\HttpService();
        $service->setDefaultAdapter($adapter);
        $this->driver->setHttpService($service);
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
