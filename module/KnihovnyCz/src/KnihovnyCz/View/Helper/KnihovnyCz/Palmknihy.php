<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use KnihovnyCz\Service\PalmknihyApiService;
use Laminas\View\Helper\AbstractHelper;
use VuFind\Db\Entity\UserEntityInterface;

/**
 * Class Palmknihy
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Palmknihy extends AbstractHelper
{
    /**
     * Constructor
     *
     * @param PalmknihyApiService $palmknihyService Palmknihy API service
     */
    public function __construct(protected PalmknihyApiService $palmknihyService)
    {
    }

    /**
     * Get Palmknihy enabled prefixes for user
     *
     * @param UserEntityInterface $user User
     *
     * @return array
     */
    public function getEnabledPrefixes(UserEntityInterface $user): array
    {
        return $this->palmknihyService->getEnabledPrefixes($user->getLibraryPrefixes());
    }

    /**
     * Get Palmknihy prefixes for user in libraries with books enabled
     *
     * @param UserEntityInterface $user User
     *
     * @return array
     */
    public function getEnabledPrefixesForBooks(UserEntityInterface $user): array
    {
        return $this->palmknihyService->getEnabledPrefixesForBooks($user->getLibraryPrefixes());
    }

    /**
     * Get Palmknihy prefixes for user in libraries with audiobooks enabled
     *
     * @param UserEntityInterface $user User
     *
     * @return array
     */
    public function getEnabledPrefixesForAudioBooks(UserEntityInterface $user): array
    {
        return $this->palmknihyService->getEnabledPrefixesForAudiobooks($user->getLibraryPrefixes());
    }

    /**
     * Get config for libraries with audiobooks enabled
     *
     * @return array
     */
    public function getEnabledConfigForAudioBooks(): array
    {
        return $this->palmknihyService->getEnabledConfigForAudioBooks();
    }

    /**
     * Get config for libraries with audiobooks enabled
     *
     * @return array
     */
    public function getEnabledConfigForBooks(): array
    {
        return $this->palmknihyService->getEnabledConfigForBooks();
    }

    /**
     * Check if the service is configured for a single institution.
     *
     * @return bool
     */
    public function isSingleInstitution(): bool
    {
        return $this->palmknihyService->isSingleInstitution();
    }
}
