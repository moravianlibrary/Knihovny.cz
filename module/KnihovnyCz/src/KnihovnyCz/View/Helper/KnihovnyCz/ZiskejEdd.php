<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use KnihovnyCz\Ziskej;
use Laminas\View\Helper\AbstractHelper;
use Mzk\ZiskejApi\Enum\StatusName;

/**
 * Ziskej Edd View Helper
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejEdd extends AbstractHelper
{
    /**
     * Ziskej EDD model
     *
     * @var \KnihovnyCz\Ziskej\ZiskejEdd
     */
    private Ziskej\ZiskejEdd $cpkZiskej;

    /**
     * Constructor
     *
     * @param \KnihovnyCz\Ziskej\ZiskejEdd $cpkZiskej Ziskej EDD model
     */
    public function __construct(Ziskej\ZiskejEdd $cpkZiskej)
    {
        $this->cpkZiskej = $cpkZiskej;
    }

    /**
     * Return if Ziskej is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->cpkZiskej->isEnabled();
    }

    /**
     * Get current Ziskej mode
     *
     * @return string
     */
    public function getCurrentMode(): string
    {
        return $this->cpkZiskej->getCurrentMode();
    }

    /**
     * Return if Ziskej is in production mode
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->cpkZiskej->getCurrentMode()
            === Ziskej\ZiskejEdd::MODE_PRODUCTION;
    }

    /**
     * Get available Ziskej modes
     *
     * @return string[]
     */
    public function getModes(): array
    {
        return $this->cpkZiskej->getModes();
    }

    /**
     * Get html class attribute
     *
     * @param StatusName|null $status Ziskej ticket status
     *
     * @return string
     */
    public function getStatusClass(?StatusName $status = null): string
    {
        return match ($status) {
            StatusName::CREATED, StatusName::PAID => 'warning',
            StatusName::ACCEPTED, StatusName::PREPARED, StatusName::LENT => 'success',
            StatusName::REJECTED => 'danger',
            default => 'default',
        };
    }
}
