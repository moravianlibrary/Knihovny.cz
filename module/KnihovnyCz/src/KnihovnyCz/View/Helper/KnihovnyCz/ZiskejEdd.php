<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

/**
 * Ziskej Edd View Helper
 */
class ZiskejEdd extends AbstractHelper
{

    /**
     * @var \KnihovnyCz\Ziskej\ZiskejEdd
     */
    private $cpkZiskejEdd;

    public function __construct(\KnihovnyCz\Ziskej\ZiskejEdd $cpkZiskej)
    {
        $this->cpkZiskejEdd = $cpkZiskej;
    }

    public function isEnabled(): bool
    {
        return $this->cpkZiskejEdd->isEnabled();
    }

    public function getCurrentMode(): string
    {
        return $this->cpkZiskejEdd->getCurrentMode();
    }

    public function isProduction(): bool
    {
        return $this->cpkZiskejEdd->getCurrentMode() === \KnihovnyCz\Ziskej\ZiskejEdd::MODE_PRODUCTION;
    }

    public function getModes()
    {
        return $this->cpkZiskejEdd->getModes();
    }
}
