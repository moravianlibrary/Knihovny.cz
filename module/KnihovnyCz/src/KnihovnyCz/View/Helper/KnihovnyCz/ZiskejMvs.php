<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use KnihovnyCz\Ziskej;
use Laminas\View\Helper\AbstractHelper;
use Mzk\ZiskejApi\Enum\StatusName;

/**
 * Ziskej View Helper
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejMvs extends AbstractHelper
{
    /**
     * Ziskej ILL model
     *
     * @var \KnihovnyCz\Ziskej\ZiskejMvs
     */
    private Ziskej\ZiskejMvs $cpkZiskej;

    /**
     * Constructor
     *
     * @param \KnihovnyCz\Ziskej\ZiskejMvs $cpkZiskej Ziskej ILL model
     */
    public function __construct(Ziskej\ZiskejMvs $cpkZiskej)
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
            === Ziskej\ZiskejMvs::MODE_PRODUCTION;
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
    public function getStatusClass(StatusName $status = null): string
    {
        return match ($status) {
            StatusName::CREATED, StatusName::PAID => 'warning',
            StatusName::ACCEPTED, StatusName::PREPARED, StatusName::LENT => 'success',
            StatusName::REJECTED => 'danger',
            default => 'default',
        };
    }
}
