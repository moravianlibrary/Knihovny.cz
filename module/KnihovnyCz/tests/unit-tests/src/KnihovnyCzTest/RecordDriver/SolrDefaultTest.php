<?php

namespace KnihovnyCzTest\RecordDriver;

use KnihovnyCz\RecordDriver\SolrDefault;
use VuFind\Config\Config;

/**
 * Class SolrDefaultTest
 *
 * @category Knihovny.cz
 * @package  Tests
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SolrDefaultTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test getCleanUuid with UUID in the record
     *
     * @return void
     */
    public function testGetCleanUuidFromRecord(): void
    {
        $driver = $this->createDriver(['uuid_display_mv' => ['uuid1', 'uuid2']]);
        $this->assertEquals('uuid1', $driver->getCleanUuid());
    }

    /**
     * Test getCleanUuid with no UUID in record but in parent
     *
     * @return void
     */
    public function testGetCleanUuidFromParent(): void
    {
        $parentDriver = $this->createDriver(['uuid_display_mv' => ['parent-uuid']]);

        $driver = $this->getMockBuilder(SolrDefault::class)
            ->setConstructorArgs([new Config([])])
            ->onlyMethods(['getParentRecord'])
            ->getMock();

        $driver->expects($this->once())
            ->method('getParentRecord')
            ->willReturn($parentDriver);

        $driver->setRawData([]);

        $this->assertEquals('parent-uuid', $driver->getCleanUuid());
    }

    /**
     * Test getCleanUuid with no UUID in either
     *
     * @return void
     */
    public function testGetCleanUuidReturnsFalse(): void
    {
        // Case 1: No parent record
        // We need to mock getParentRecord to return null to avoid it trying to load things
        $driver = $this->getMockBuilder(SolrDefault::class)
            ->setConstructorArgs([new Config([])])
            ->onlyMethods(['getParentRecord'])
            ->getMock();

        $driver->expects($this->once())
            ->method('getParentRecord')
            ->willReturn(null);
        $driver->setRawData([]);

        $this->assertFalse($driver->getCleanUuid());

        // Case 2: Parent record has no UUID
        $parentDriver = $this->createDriver([]);

        $driver = $this->getMockBuilder(SolrDefault::class)
            ->setConstructorArgs([new Config([])])
            ->onlyMethods(['getParentRecord'])
            ->getMock();

        $driver->expects($this->once())
            ->method('getParentRecord')
            ->willReturn($parentDriver);
        $driver->setRawData([]);

        $this->assertFalse($driver->getCleanUuid());
    }

    /**
     * Helper to create a driver instance with data
     *
     * @param array $data Data to set in the driver
     *
     * @return SolrDefault
     */
    protected function createDriver(array $data): SolrDefault
    {
        $config = new Config([]);
        $driver = new SolrDefault($config);
        $driver->setRawData($data);
        return $driver;
    }
}
