<?php

/**
 * Trait CitaceProTrait
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
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

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
        return $this->citacePro->getCitation($this->getUniqueID(), $style);
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
