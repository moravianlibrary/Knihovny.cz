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
namespace KnihovnyCz\RecordDriver;

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
     * TODO: Do we really need these two methods? If so, shouldn't it be rendered
     * in template?
     */
    public function getPatentInfo(): string
    {
        $patentInfo = [];
        $patentInfo['country'] = $this->getFieldArray('013', ['b'])[0];
        $patentInfo['type'] = $this->getFieldArray('013', ['c'])[0];
        $patentInfo['id'] = $this->getFieldArray('013', ['a'])[0];
        $patentInfo['publish_date'] = $this->getFieldArray('013', ['d'])[0];
        if (empty($patentInfo)) {
            return '';
        }
        return $this->renderPatentInfo($patentInfo);
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
        $patentInfoText = '';
        $patentInfoText .= $this->translate('Patent') . ': '
            . $patentInfo['country'] . ', ';
        switch ($patentInfo['type']) {
        case 'B6':
            $patentInfoText .= $this->translate('patent_file');
            break;
        case 'A3':
            $patentInfoText .= $this->translate('app_invention');
            break;
        case 'U1':
            $patentInfoText .= $this->translate('utility_model');
            break;
        default:
            $patentInfoText .= $this->translate('unknown_patent_type');
            break;
        }
        $patentInfoText = implode(
            ',', [$patentInfoText, $patentInfo['id'], $patentInfo['publish_date']]
        ) . "\r\n";
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
            $fields024, function ($part) {
                return isset($part['2']) && ($part['2'] === 'MPT');
            }
        );
        $mpts = array_map(
            function ($part) {
                return $part['a'];
            }, $mpts
        );
        return $mpts;
    }
}
