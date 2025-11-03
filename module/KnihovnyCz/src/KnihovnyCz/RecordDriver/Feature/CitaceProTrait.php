<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordDriver\Feature;

use KnihovnyCz\Service\CitaceProService;

/**
 * Trait CitaceProTrait
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait CitaceProTrait
{
    /**
     * CitacePro API service
     */
    protected CitaceProService $citacePro;

    /**
     * Attach CitacePro service to record driver
     *
     * @param CitaceProService $citacePro CitacePro API service
     *
     * @return void
     */
    public function attachCitaceProService(CitaceProService $citacePro): void
    {
        $this->citacePro = $citacePro;
    }

    /**
     * Get citation formats
     *
     * @return array
     */
    public function getCitationFormats(): array
    {
        return $this->citacePro->getCitationStyles();
    }

    /**
     * Get default citation style identifier
     *
     * @return string
     */
    public function getDefaultCitationStyle(): string
    {
        return $this->citacePro->getDefaultCitationStyle();
    }

    /**
     * Get citation HTML snippet
     *
     * @param string|null $style Style identifier
     *
     * @return string
     * @throws \Exception
     */
    public function getCitation(?string $style = null): string
    {
        return $this->citacePro->getCitation($this->getUniqueID(), $style, $this->getSourceIdentifier());
    }

    /**
     * Get Citation as plain text
     *
     * @param string|null $style Style identifier
     *
     * @return string
     * @throws \Exception
     */
    public function getCitationPlaintext(?string $style = null): string
    {
        return $this->citacePro->getCitation($this->getUniqueID(), $style, $this->getSourceIdentifier(), true);
    }

    /**
     * Get link to citacepro.com
     *
     * @return string
     * @throws \Exception
     */
    public function getCitationLink(): string
    {
        return $this->citacePro->getCitationLink($this->getUniqueID());
    }
}
