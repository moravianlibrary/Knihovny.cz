<?php

declare(strict_types=1);

namespace KnihovnyCzApiTest\Controller;

use KnihovnyCzApi\Controller\SearchApiController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class SearchApiControllerTest
 *
 * @category Knihovny.cz
 * @package  Tests
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SearchApiControllerTest extends TestCase
{
    protected SearchApiController $controller;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->controller = $this->getMockBuilder(SearchApiController::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Call the protected redirectToLibraryRoute method via reflection
     *
     * @param string|array $ids Single ID or array of IDs
     *
     * @return bool
     */
    private function callRedirectToLibraryRoute(string|array $ids): bool
    {
        $method = new \ReflectionMethod(SearchApiController::class, 'redirectToLibraryRoute');
        return $method->invoke($this->controller, $ids);
    }

    /**
     * Data provider for redirectToLibraryRoute with string input
     *
     * @return array
     */
    public static function stringIdProvider(): array
    {
        return [
            'library string returns true' => ['library.ABC123', true],
            'biblio string returns false' => ['biblio.123', false],
            'empty string returns false'  => ['', false],
        ];
    }

    /**
     * Test redirectToLibraryRoute with string IDs
     *
     * @param string $id       String ID to test
     * @param bool   $expected Expected result
     *
     * @return void
     */
    #[DataProvider('stringIdProvider')]
    public function testRedirectToLibraryRouteWithString(string $id, bool $expected): void
    {
        $this->assertEquals($expected, $this->callRedirectToLibraryRoute($id));
    }

    /**
     * Data provider for redirectToLibraryRoute with array input
     *
     * @return array
     */
    public static function arrayIdProvider(): array
    {
        return [
            'all library returns true'  => [['library.1', 'library.2'], true],
            'no library returns false'  => [['biblio.1', 'biblio.2'], false],
            'empty array returns false' => [[], false],
        ];
    }

    /**
     * Test redirectToLibraryRoute with array IDs
     *
     * @param array $ids      Array of IDs to test
     * @param bool  $expected Expected result
     *
     * @return void
     **/
    #[DataProvider('arrayIdProvider')]
    public function testRedirectToLibraryRouteWithArray(array $ids, bool $expected): void
    {
        $this->assertEquals($expected, $this->callRedirectToLibraryRoute($ids));
    }

    /**
     * Test that redirectToLibraryRoute throws InvalidArgumentException on mixed array
     *
     * @return void
     */
    public function testRedirectToLibraryRouteThrowsOnMixedArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->callRedirectToLibraryRoute(['library.1', 'biblio.2']);
    }
}
