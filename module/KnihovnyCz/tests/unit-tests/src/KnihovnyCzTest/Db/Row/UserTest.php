<?php

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
    public static function hasPermissionProvider(): array
    {
        return [
            [
                ['id' => 1, 'major' => ''],
                'widgets',
                false,
            ],
            [
                ['id' => 1, 'major' => 'widgets'],
                'widgets',
                true,
            ],
            [
                ['id' => 1, 'major' => 'admin, widgets'],
                'widgets',
                true,
            ],
            [
                ['id' => 1, 'major' => ''],
                'any',
                false,
            ],
            [
                ['id' => 1, 'major' => 'admin, widgets'],
                'any',
                true,
            ],
        ];
    }

    /**
     * Test hasPermission method
     *
     * @param array  $userData   User data
     * @param string $permission Permission
     * @param bool   $expected   Expected result
     *
     * @return void
     *
     * @dataProvider hasPermissionProvider
     */
    public function testHasPermission(array $userData, string $permission, bool $expected): void
    {
        $user = $this->getMockUser($userData);
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
