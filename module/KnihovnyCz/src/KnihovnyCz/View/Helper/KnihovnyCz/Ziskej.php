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
        switch ($status) {
            case 'created':
                return 'warning';
                break;
            case 'paid':
                return 'warning';
                break;
            case 'accepted':
                return 'success';
                break;
            case 'prepared':
                return 'success';
                break;
            case 'lent':
                return 'success';
                break;
            case 'closed':
                return 'default';
                break;
            case 'cancelled':
                return 'default';
                break;
            case 'rejected':
                return 'danger';
                break;
            default:
                return 'default';
                break;
        }
    }
}
