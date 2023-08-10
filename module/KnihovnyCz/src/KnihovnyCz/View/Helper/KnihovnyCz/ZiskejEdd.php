<?php

/**
 * Ziskej Edd View Helper
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2019.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

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
