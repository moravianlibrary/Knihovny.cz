<?php

declare(strict_types=1);

namespace KnihovnyCzTest\Service;

use KnihovnyCz\Db\Row\User;
use KnihovnyCz\Db\Row\UserSettings;
use KnihovnyCz\Service\PreferredInstitutionsService;
use VuFind\Config\Config;
use VuFindSearch\ParamBag;

/**
 * Class PreferredInstitutionsServiceTest
 *
 * @category KnihovnyCz
 * @package  Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class PreferredInstitutionsServiceTest extends \PHPUnit\Framework\TestCase
{
    protected const INSTITUTION_FIELD = 'region_institution_facet_mv';

    protected const FACET_CONFIG = [
        'mzk' => '2/Library/JM/MZK/',
        'kjm' => '2/Library/JM/KJM/',
        'nkp' => '2/Library/PR/NKP/',
        'anl' => '1/Others/ANL/',
    ];

    /**
     * Config with institution mappings matching the facets.ini format.
     *
     * @return PreferredInstitutionsService
     */
    protected function createService(): PreferredInstitutionsService
    {
        return new PreferredInstitutionsService(null, self::FACET_CONFIG, self::INSTITUTION_FIELD);
    }

    /**
     * Config with institution mappings matching the facets.ini format.
     *
     * @param array $savedInstitutions saved institutions
     * @param array $libraryPrefixes   library prefixes
     *
     * @return PreferredInstitutionsService
     */
    protected function createServiceWithUser(
        array $savedInstitutions = [],
        array $libraryPrefixes = []
    ): PreferredInstitutionsService {
        $userSettings = $this->getMockBuilder(UserSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSavedInstitutions'])
            ->getMock();
        $userSettings->expects($this->any())
            ->method('getSavedInstitutions')
            ->willReturn($savedInstitutions);
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserSettings', 'getLibraryPrefixes'])
            ->getMock();
        $user->expects($this->any())
            ->method('getUserSettings')
            ->willReturn($userSettings);
        $user->expects($this->any())
            ->method('getLibraryPrefixes')
            ->willReturn(['mzk']);
        return new PreferredInstitutionsService($user, self::FACET_CONFIG, self::INSTITUTION_FIELD);
    }

    /**
     * Build a ParamBag with a single OR-facet fq for the institution field.
     *
     * @param string[] $values Facet path values, e.g. ["2/Library/JM/MZK/"]
     *
     * @return ParamBag
     */
    protected function makeParams(array $values): ParamBag
    {
        $quoted = implode(' OR ', array_map(fn ($v) => '"' . $v . '"', $values));
        $fq = '{!tag=' . self::INSTITUTION_FIELD . '}' . self::INSTITUTION_FIELD . ':(' . $quoted . ')';
        return new ParamBag(['fq' => [$fq]]);
    }

    // --- getSourcesForFilters ---

    /**
     * Test: no InstitutionsMappings section in config returns empty array.
     *
     * @return void
     */
    public function testGetSourcesForFiltersNoConfig(): void
    {

        $service = new PreferredInstitutionsService(null, [], self::INSTITUTION_FIELD);
        $params = $this->makeParams(['2/Library/JM/MZK/']);
        $this->assertSame([], $service->getSourcesFromParameters($params));
    }

    /**
     * Test: no fq params at all returns empty array.
     *
     * @return void
     */
    public function testGetSourcesForFiltersNoFq(): void
    {
        $service = $this->createService();
        $this->assertSame([], $service->getSourcesFromParameters(new ParamBag()));
    }

    /**
     * Test: fq on a different field is ignored.
     *
     * @return void
     */
    public function testGetSourcesForFiltersWrongField(): void
    {
        $service = $this->createService();
        $params = new ParamBag(['fq' => ['other_field:("2/Library/JM/MZK/")']]);
        $this->assertSame([], $service->getSourcesFromParameters($params));
    }

    /**
     * Test: filtering by a specific institution ("2/Library/JM/MZK/") returns only mzk.
     *
     * @return void
     */
    public function testGetSourcesForFiltersSpecificInstitution(): void
    {
        $service = $this->createService();
        $params = $this->makeParams(['2/Library/JM/MZK/']);
        $this->assertSame(['mzk'], $service->getSourcesFromParameters($params));
    }

    /**
     * Test: filtering by region ("1/Library/JM/") returns mzk and kjm (both in JM).
     *
     * @return void
     */
    public function testGetSourcesForFiltersRegionLevel(): void
    {
        $service = $this->createService();
        $params = $this->makeParams(['1/Library/JM/']);
        $this->assertSame(['mzk', 'kjm'], $service->getSourcesFromParameters($params));
    }

    /**
     * Test: filtering by top-level branch ("0/Library/") returns mzk, kjm, and nkp.
     *
     * @return void
     */
    public function testGetSourcesForFiltersTopLevel(): void
    {
        $service = $this->createService();
        $params = $this->makeParams(['0/Library/']);
        $this->assertSame(['mzk', 'kjm', 'nkp'], $service->getSourcesFromParameters($params));
    }

    /**
     * Test: multiple values in one OR facet return combined sources without duplicates.
     *
     * @return void
     */
    public function testGetSourcesForFiltersMultipleValues(): void
    {
        $service = $this->createService();
        $params = $this->makeParams(['2/Library/JM/MZK/', '2/Library/PR/NKP/']);
        $this->assertSame(['mzk', 'nkp'], $service->getSourcesFromParameters($params));
    }

    /**
     * Test: filter value with no mapping returns empty array.
     *
     * @return void
     */
    public function testGetSourcesForFiltersUnknownValue(): void
    {
        $service = $this->createService();
        $params = $this->makeParams(['2/Library/JM/UNKNOWN/']);
        $this->assertSame([], $service->getSourcesFromParameters($params));
    }

    /**
     * Test: "Others" branch has its own independent hierarchy.
     *
     * @return void
     */
    public function testGetSourcesForFiltersOthersBranch(): void
    {
        $service = $this->createService();
        $params = $this->makeParams(['0/Others/']);
        $this->assertSame(['anl'], $service->getSourcesFromParameters($params));
    }

    // --- getFiltersForSources ---

    /**
     * Test: no InstitutionsMappings section in config returns empty array.
     *
     * @return void
     */
    public function testGetFiltersForSourcesNoConfig(): void
    {
        $service = new PreferredInstitutionsService(null, [], self::INSTITUTION_FIELD);
        $this->assertSame([], $service->getFiltersForSources(['mzk']));
    }

    /**
     * Test: empty prefixes list returns empty array.
     *
     * @return void
     */
    public function testGetFiltersForSourcesEmptyPrefixes(): void
    {
        $service = $this->createService();
        $this->assertSame([], $service->getFiltersForSources([]));
    }

    /**
     * Test: single matching prefix returns its filter value.
     *
     * @return void
     */
    public function testGetFiltersForSourcesSingleMatch(): void
    {
        $service = $this->createService();
        $this->assertSame(['2/Library/JM/MZK/'], $service->getFiltersForSources(['mzk']));
    }

    /**
     * Test: multiple matching prefixes return all their filter values.
     *
     * @return void
     */
    public function testGetFiltersForSourcesMultipleMatches(): void
    {
        $service = $this->createService();
        $this->assertSame(
            ['2/Library/JM/MZK/', '2/Library/PR/NKP/'],
            $service->getFiltersForSources(['mzk', 'nkp'])
        );
    }

    /**
     * Test: prefix with no mapping is silently skipped.
     *
     * @return void
     */
    public function testGetFiltersForSourcesUnknownPrefix(): void
    {
        $service = $this->createService();
        $this->assertSame([], $service->getFiltersForSources(['unknown']));
    }

    /**
     * Test: mix of known and unknown prefixes returns only known filter values.
     *
     * @return void
     */
    public function testGetFiltersForSourcesMixedPrefixes(): void
    {
        $service = $this->createService();
        $this->assertSame(['2/Library/PR/NKP/'], $service->getFiltersForSources(['unknown', 'nkp']));
    }

    /**
     * Test: empty parameters and user has library card in MZK
     *
     * @return void
     */
    public function testGetSourcesFromEmptyParametersAndUserLibraryCards(): void
    {
        $service = $this->createServiceWithUser([], ['mzk']);
        $this->assertSame(['mzk'], $service->getSourcesFromParametersAndUser($this->makeParams([])));
    }

    /**
     * Test: Library cards are ignored when user has saved institutions
     *
     * @return void
     */
    public function testGetSourcesFromEmptyParametersAndUserSavedInstitutions(): void
    {
        $service = $this->createServiceWithUser(['2/Library/PR/NKP/'], ['mzk']);
        $this->assertSame(['nkp'], $service->getSourcesFromParametersAndUser($this->makeParams([])));
    }

    /**
     * Test: overlap of user's preferred institutions and filters has precedence
     *
     * @return void
     */
    public function testGetSourcesFromOverlap1(): void
    {
        $service = $this->createServiceWithUser(['2/Library/PR/NKP/',
            '2/Library/JM/MZK/'], ['mzk']);
        $this->assertSame(['mzk', 'nkp'], $service->getSourcesFromParametersAndUser(
            $this->makeParams(['2/Library/JM/MZK/'])
        ));
    }

    /**
     * Test: overlap of user's preferred institutions and filters has precedence
     *
     * @return void
     */
    public function testGetSourcesFromOverlap2(): void
    {
        $service = $this->createServiceWithUser(['2/Library/PR/NKP/',
            '2/Library/JM/MZK/'], ['mzk']);
        $this->assertSame(['nkp', 'mzk'], $service->getSourcesFromParametersAndUser(
            $this->makeParams(['2/Library/PR/NKP/'])
        ));
    }

    /**
     * Test: applied filters have precedence over user's preferred institutions
     *
     * @return void
     */
    public function testGetSourcesFromParametersAndUser(): void
    {
        $service = $this->createServiceWithUser(['2/Library/PR/NKP/', '2/Library/JM/MZK/'], []);
        $this->assertSame(['anl', 'nkp', 'mzk'], $service->getSourcesFromParametersAndUser(
            $this->makeParams(['1/Others/ANL/'])
        ));
    }
}
