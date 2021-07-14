<?php
declare(strict_types=1);

/**
 * Class MutiBackendTest
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
 * but WITHOUT ANY WARRANTY; without even the im25faff9a9c06052a1ce169ce852d687df4814137plied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category CPK-vufind-6
 * @package  KnihovnyCzTest\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCzTest\ILS\Driver;

use KnihovnyCz\Db\Table\InstConfigs;
use KnihovnyCz\Db\Table\InstSources;
use KnihovnyCz\ILS\Driver\MultiBackend;
use KnihovnyCz\ILS\Service\SolrIdResolver;

/**
 * Class MutiBackendTest
 *
 * @category CPK-vufind-6
 * @package  KnihovnyCzTest\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class MultiBackendTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Test siglaToSource method
     *
     * @throws \VuFind\Exception\ILS
     */
    public function testSiglaToSource(): void
    {
        $config = [
            'Drivers' => [],
            'SiglaMapping' => [
                'aaa' => 'AAA001',
                'bbb' => 'BBB001',
                'ccc' => 'CCC001'
            ],
        ];
        $driver = $this->getDriver();
        $driver->setConfig($config);
        $driver->init();
        $this->assertEquals('aaa', $driver->siglaToSource('AAA001'));
        $this->assertEquals(null, $driver->siglaToSource('QQQ001'));
        $config = [
            'Drivers' => [],
            'SiglaMapping' => [
            ],
        ];
        $driver = $this->getDriver();
        $driver->setConfig($config);
        $driver->init();
        $this->assertEquals(null, $driver->siglaToSource('AAA001'));
        $config = [
            'Drivers' => [],
        ];
        $driver = $this->getDriver();
        $driver->setConfig($config);
        $driver->init();
        $this->assertEquals(null, $driver->siglaToSource('AAA001'));
    }

    /**
     * Test sourceToSigla method
     *
     * @throws \VuFind\Exception\ILS
     */
    public function testSourceToSigla(): void
    {
        $config = [
            'Drivers' => [],
            'SiglaMapping' => [
                'aaa' => 'AAA001',
                'bbb' => 'BBB001',
                'ccc' => 'CCC001'
            ],
        ];
        $driver = $this->getDriver();
        $driver->setConfig($config);
        $driver->init();
        $this->assertEquals('BBB001', $driver->sourceToSigla('bbb'));
        $this->assertEquals(null, $driver->sourceToSigla('qqq'));
        $config = [
            'Drivers' => [],
            'SiglaMapping' => [
            ],
        ];
        $driver = $this->getDriver();
        $driver->setConfig($config);
        $driver->init();
        $this->assertEquals(null, $driver->sourceToSigla('aaa'));
        $config = [
            'Drivers' => [],
        ];
        $driver = $this->getDriver();
        $driver->setConfig($config);
        $driver->init();
        $this->assertEquals(null, $driver->sourceToSigla('aaa'));
    }


    /**
     * Method to get a fresh MultiBackend Driver.
     *
     * @param array  $config Driver configuration
     * @param object $sm     Service manager (null for default mock)
     *
     * @return mixed A MultiBackend instance.
     */
    protected function getDriver($sm = null)
    {
        $driver = new MultiBackend(
            $this->getPluginManager(), $this->getMockILSAuthenticator(),
            $sm ?? $this->getMockSM(), $this->getMockInstConfigs(),
            $this->getMockInstSources(), $this->getMockSolrIdResolver()
        );
        $config = [
            'Drivers' => [],
            'Login' => [
                'drivers' => ['d1', 'd2'],
                'default_driver' => 'd1'
            ],
        ];
        $driver->setConfig($config);
        $driver->init();
        return $driver;
    }

    protected function getMockInstConfigs(): InstConfigs
    {
        $mock = $this->getMockBuilder(InstConfigs::class)
            ->disableOriginalConstructor()
            ->getMock();
        return $mock;
    }

    protected function getMockInstSources(): InstSources
    {
        $mock = $this->getMockBuilder(InstSources::class)
            ->disableOriginalConstructor()
            ->getMock();
        return $mock;
    }

    protected function getMockSolrIdResolver(): SolrIdResolver
    {
        $mock = $this->getMockBuilder(SolrIdResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        return $mock;
    }

    /**
     * Method to get a fresh Plugin Manager.
     *
     * @return mixed A MultiBackend instance.
     */
    protected function getPluginManager()
    {
        $configData = ['config' => 'values'];
        $config = new \Laminas\Config\Config($configData);
        $mockPM = $this->createMock(\VuFind\Config\PluginManager::class);
        $mockPM->expects($this->any())
            ->method('get')
            ->will($this->returnValue($config));
        return $mockPM;
    }

    /**
     * Get a mock ILS authenticator
     *
     * @param bool $userSource Source id, if the authenticator should emulate a
     * situation where a user has logged in
     *
     * @return \VuFind\Auth\ILSAuthenticator
     */
    protected function getMockILSAuthenticator($userSource = '')
    {
        $mockAuth = $this->getMockBuilder(\VuFind\Auth\ILSAuthenticator::class)
            ->disableOriginalConstructor()
            ->getMock();
        if ($userSource) {
            $mockAuth->expects($this->any())
                ->method('storedCatalogLogin')
                ->will(
                    $this->returnValue($this->getPatron('username', $userSource))
                );
            $mockAuth->expects($this->any())
                ->method('getStoredCatalogCredentials')
                ->will(
                    $this->returnValue($this->getPatron('username', $userSource))
                );
        }
        return $mockAuth;
    }

    /**
     * This function returns a mock service manager with the given parameters
     * For examples of what is to be passed, see:
     * http://www.phpunit.de/manual/3.0/en/mock-objects.html
     *
     * @param object $times  The number of times it is expected to be called.
     * @param object $driver The driver type this SM will expect to be called with.
     * @param mixed  $return What that get function should return.
     *
     * @return object The Mock Service Manager created.
     */
    protected function getMockSM($times = null, $driver = 'Voyager', $return = null)
    {
        $sm = $this->getMockBuilder(\VuFind\ILS\Driver\PluginManager::class)
            ->disableOriginalConstructor()->getMock();
        $sm->expects($times ?? $this->any())
            ->method('get')
            ->with($driver)
            ->will($this->returnValue($return));
        return $sm;
    }
}
