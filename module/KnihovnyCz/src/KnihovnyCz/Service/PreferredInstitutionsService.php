<?php

declare(strict_types=1);

namespace KnihovnyCz\Service;

use KnihovnyCz\Db\Row\User;
use VuFindSearch\ParamBag;

/**
 * Service for preferred institutions - from facet filters or user preferences
 *
 * @category VuFind
 * @package  Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class PreferredInstitutionsService
{
    public const OR_FACETS_REGEX = '/(\\{[^\\}]*\\})*([^\\(\s]+):\\(([^\\)]+)\\)/';

    public const FILTER_REGEX = '/"([^"]+)"/';

    /**
     * Lazily-built reverse mapping: hierarchical path key => source prefixes
     *
     * @var ?array
     */
    private ?array $reverseMappings = null;

    /**
     * Constructor.
     *
     * @param User|null $user             Auth manager
     * @param array     $mappings         Facet configuration
     * @param string    $institutionField Institution field
     */
    public function __construct(
        private readonly ?User $user,
        private readonly array $mappings,
        private readonly string $institutionField
    ) {
    }

    /**
     * Get source prefixes matching active institution facet filters.
     *
     * @param ParamBag $params Search parameters
     *
     * @return array
     */
    public function getSourcesFromParameters(ParamBag $params): array
    {
        $values = [];
        foreach ($params->get('fq') ?? [] as $fq) {
            if (preg_match(self::OR_FACETS_REGEX, $fq, $matches)) {
                $field = $matches[2];
                if ($field !== $this->institutionField) {
                    continue;
                }
                $filters = explode('OR', $matches[3]);
                foreach ($filters as $filter) {
                    if (preg_match(self::FILTER_REGEX, $filter, $filterMatches)) {
                        $values[] = $filterMatches[1];
                    }
                }
            }
        }
        return $this->getSources($values);
    }

    /**
     * Get source prefixes matching active institution facet filters.
     *
     * @param ParamBag $params Search parameters
     *
     * @return array
     */
    public function getSourcesFromParametersAndUser(ParamBag $params): array
    {
        $fromFilters = $this->getSourcesFromParameters($params);
        $fromCards = $this->getSourcesFromUser();
        $inBoth = array_intersect($fromCards, $fromFilters);
        return array_values(array_unique(array_merge($inBoth, $fromFilters, $fromCards)));
    }

    /**
     * Get institution facet filter values for the given source prefixes.
     *
     * @param array $prefixes Source prefixes
     *
     * @return array
     */
    public function getFiltersForSources(array $prefixes): array
    {
        $filters = [];
        foreach ($this->mappings as $source => $filter) {
            if (in_array($source, $prefixes)) {
                $filters[] = $filter;
            }
        }
        return $filters;
    }

    /**
     * Get institution filters from user
     *
     * @return array
     */
    public function getFiltersFromUser(): array
    {
        if (!$this->user) {
            return [];
        }
        $savedInstitutions = $this->user->getUserSettings()->getSavedInstitutions();
        if (!empty($savedInstitutions)) {
            return $savedInstitutions;
        }
        return $this->getFiltersForSources($this->user->getLibraryPrefixes()) ?? [];
    }

    /**
     * Get sources from user's library cards
     *
     * @return array
     */
    public function getSourcesFromUser(): array
    {
        if (!$this->user) {
            return [];
        }
        $savedInstitutions = $this->user->getUserSettings()->getSavedInstitutions();
        if (!empty($savedInstitutions)) {
            return $this->getSources($savedInstitutions);
        }
        return $this->user->getLibraryPrefixes() ?? [];
    }

    /**
     * Build and cache the reverse mapping from hierarchical path keys to sources.
     *
     * @return array
     */
    private function getReverseMappings(): array
    {
        if ($this->reverseMappings !== null) {
            return $this->reverseMappings;
        }
        if (empty($this->mappings)) {
            return $this->reverseMappings = [];
        }
        $mappings = [];
        foreach ($this->mappings as $source => $filter) {
            $index = 0;
            $path = '';
            $elements = array_slice(explode('/', $filter), 1);
            foreach ($elements as $element) {
                if (empty($element)) {
                    break;
                }
                $path .= '/' . $element;
                $mappings[$index . $path . '/'][] = $source;
                $index++;
            }
        }
        return $this->reverseMappings = $mappings;
    }

    /**
     * Get sources from filters
     *
     * @param array $values filters
     *
     * @return array
     */
    private function getSources(array $values): array
    {
        $reverseMappings = $this->getReverseMappings();
        $sources = [];
        foreach ($values as $value) {
            $prefixes = $reverseMappings[$value] ?? null;
            if ($prefixes) {
                array_push($sources, ...$prefixes);
            }
        }
        return $sources;
    }
}
