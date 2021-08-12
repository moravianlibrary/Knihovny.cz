<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

/**
 * Ziskej View Helper
 */
class Ziskej extends AbstractHelper
{

    /**
     * @var \KnihovnyCz\Ziskej\ZiskejMvs
     */
    private $cpkZiskej;

    public function __construct(\KnihovnyCz\Ziskej\ZiskejMvs $cpkZiskej)
    {
        $this->cpkZiskej = $cpkZiskej;
    }

    public function isEnabled(): bool
    {
        return $this->cpkZiskej->isEnabled();
    }

    public function getCurrentMode(): string
    {
        return $this->cpkZiskej->getCurrentMode();
    }

    public function isProduction(): bool
    {
        return $this->cpkZiskej->getCurrentMode() === \KnihovnyCz\Ziskej\ZiskejMvs::MODE_PRODUCTION;
    }

    public function getModes()
    {
        return $this->cpkZiskej->getModes();
    }

    public function getStatusClass(string $status = null): string
    {
        return match ($status) {
            'created', 'paid' => 'warning',
            'accepted', 'prepared', 'lent' => 'success',
            'rejected' => 'danger',
            'closed', 'cancelled' => 'default',
            default => 'default',
        };
    }
}
