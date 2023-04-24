<?php

/**
 * Trait PatentTrait
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

namespace KnihovnyCz\RecordDriver\Feature;

/**
 * Trait PatentTrait
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait PatentTrait
{
    /**
     * Get patent info for export in txt
     *
     * @return string
     */
    public function getPatentInfo(): string
    {
        $patentInfo = [];
        $subfields = [
            'b' => 'country',
            'c' => 'type',
            'a' => 'id',
            'd' => 'publish_date'
        ];
        foreach ($subfields as $subfield => $patentInfoKey) {
            $data = $this->getFieldArray('013', [$subfield]);
            if (!empty($data)) {
                $patentInfo[$patentInfoKey] = $data[0];
            }
        }
        return empty($patentInfo) ? '' : $this->renderPatentInfo($patentInfo);
    }

    /**
     * Render patent info to export file
     *
     * @param array $patentInfo array with patent info
     *
     * @return string rendered string
     */
    public function renderPatentInfo(array $patentInfo): string
    {
        $patentType = match ($patentInfo['type'] ?? '') {
            'B6' => 'patent_file',
            'A3' => 'app_invention',
            'U1' => 'utility_model',
            default => 'unknown_patent_type',
        };
        $patentInfoText = $patentInfo['country'];
        $patentInfoText .= ', ' . $this->translate($patentType);
        $patentInfoText .= !empty($patentInfo['id']) ? ', ' . $patentInfo['id'] : '';
        $patentInfoText .= !empty($patentInfo['publish_date'])
            ? ', ' . $patentInfo['publish_date'] : '';
        return $patentInfoText;
    }

    /**
     * Get international patent classification
     *
     * @return array
     */
    public function getMpts(): array
    {
        $fields024 = $this->getStructuredDataFieldArray('024');
        $mpts = array_filter(
            $fields024,
            function ($part) {
                return isset($part['2']) && ($part['2'] === 'MPT');
            }
        );
        $mpts = array_map(
            function ($part) {
                return $part['a'];
            },
            $mpts
        );
        return $mpts;
    }
}
