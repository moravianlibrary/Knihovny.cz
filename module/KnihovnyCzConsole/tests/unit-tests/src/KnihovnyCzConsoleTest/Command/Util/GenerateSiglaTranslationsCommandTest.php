<?php

declare(strict_types=1);

namespace KnihovnyCzConsoleTest\Command\Util;

use KnihovnyCzConsole\Command\Util\GenerateSiglaTranslationsCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VuFindTest\Feature\ReflectionTrait;

/**
 * Class GenerateSiglaTranslationsCommandTest
 *
 * @category Knihovny.cz
 * @package  Tests
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GenerateSiglaTranslationsCommandTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Data provider for testTownInTitle
     *
     * @return array
     */
    public static function townInTitleProvider(): array
    {
        return [
            'town stem matches Czech title' => [
                'Praha', 'Městská knihovna Praha', false, true,
            ],
            'town not present in title' => [
                'Vsetín', 'Masarykova veřejná knihovna', false, false,
            ],
            'matching is case insensitive' => [
                'Brno', 'Knihovna BRNO - pobočka Kraví hora', false, true,
            ],
            'diacritics kept - no match' => [
                'Tábor', 'Městská knihovna Tabor', false, false,
            ],
            'diacritics stripped - match' => [
                'Tábor', 'Municipal library Tabor', true, true,
            ],
            'match comes from extra stems' => [
                'Stařeč', 'Knihovna ve Starči', false, true,
            ],
            'hyphenated town name is split' => [
                'Frýdek-Místek', 'Knihovna Frýdek', false, true,
            ],
            'short town name matches whole word' => [
                'Aš', 'Knihovna Aš', false, true,
            ],
            'town with extra stems not present in title' => [
                'Stařeč', 'Vědecká knihovna Olomouc', false, false,
            ],
        ];
    }

    /**
     * Test townInTitle method
     *
     * @param string $town            Town name
     * @param string $title           Title to test
     * @param bool   $stripDiacritics Whether to strip diacritics
     * @param bool   $expected        Expected result
     *
     * @return void
     **/
    #[DataProvider('townInTitleProvider')]
    public function testTownInTitle(
        string $town,
        string $title,
        bool $stripDiacritics,
        bool $expected
    ): void {
        $command = (new \ReflectionClass(GenerateSiglaTranslationsCommand::class))->newInstanceWithoutConstructor();
        $result = $this->callMethod(
            $command,
            'townInTitle',
            [$town, $title, $stripDiacritics]
        );
        $this->assertSame($expected, $result);
    }
}
