<?php

/**
 * Class UserTest
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2023.
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
 * @package  Tests
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCzTest\Db\Table;

use KnihovnyCz\Db\Row\User;

/**
 * Class UserTest
 *
 * @category Knihovny.cz
 * @package  Tests
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Data provider for test hasPermission method
     *
     * @return array
     */
    public function hasPermissionProvider(): array
    {
        return [
            [
                $this->getMockUser(['id' => 1, 'major' => '']),
                'widgets',
                false,
            ],
            [
                $this->getMockUser(['id' => 1, 'major' => 'widgets']),
                'widgets',
                true,
            ],
            [
                $this->getMockUser(['id' => 1, 'major' => 'admin, widgets']),
                'widgets',
                true,
            ],
            [
                $this->getMockUser(['id' => 1, 'major' => '']),
                'any',
                false,
            ],
            [
                $this->getMockUser(['id' => 1, 'major' => 'admin, widgets']),
                'any',
                true,
            ],
        ];
    }

    /**
     * Test hasPermission method
     *
     * @param User   $user       User
     * @param string $permission Permission
     * @param bool   $expected   Expected result
     *
     * @return void
     *
     * @dataProvider hasPermissionProvider
     */
    public function testHasPermission(User $user, string $permission, bool $expected): void
    {
        $this->assertEquals($expected, $user->hasPermission($permission));
    }

    /**
     * Mock user
     *
     * @param array $params User properties
     *
     * @return User
     */
    protected function getMockUser(array $params): User
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()->getMock();
        foreach ($params as $key => $value) {
            $user->$key = $value;
        }
        return $user;
    }
}
