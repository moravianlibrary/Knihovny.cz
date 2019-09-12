<?php
/**
 * Knihovny.cz trait for handling marc field 996 in record drivers
 *
 * PHP version 7
 *
 * Copyright (C) The Moravian Library 2015-2019.
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
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */

namespace KnihovnyCz\RecordDriver;

trait MarcField996AwareTrait
{
    protected $sortFields =  ['y', 'v', 'i'];
    protected $ilsConfig = null;

    protected function getIlsConfig(): array
    {
        if ($this->ilsConfig === null && $this->hasILS()) {
              $this->ilsConfig = $this->ils->getDriverConfig();
        }
        return $this->ilsConfig;
    }

    /**
     * Returns array of holdings parsed via indexed 996 fields.
     *
     * @return array
     */
    protected function parseHoldingsFrom996field(): array
    {
        $fields = $this->getStructuredDataFieldArray("996");
        $fields = $this->filterOutRestrictedItems($fields);
        $fields = $this->sortFields($fields);

        return array_map([$this, 'processField'], $fields);
    }

    protected function processField($field)
    {
        $mappings = $this->get996Mappings();
        $holding = [];
        foreach ($mappings['data'] as $variableName => $current996Mapping) {
            // Here it omits unset values & values, which are desired to be ignored by their presence in ignoredVals MultiBackend.ini's array
            if (!empty($field[$current996Mapping])
                && !$this->isIgnored(
                    $field[$current996Mapping], $current996Mapping,
                    $mappings['ignoredVals']
                )
            ) {
                $holding[$variableName] = $field[$current996Mapping];
            }
        }

        // Translation takes place from translate
        foreach ($mappings['translate'] as $fieldToTranslate => $prependString) {
            if (!empty($holding[$fieldToTranslate])) {
                $holding[$fieldToTranslate] = $this->translate(
                    $prependString . $holding[$fieldToTranslate], [],
                    $holding[$fieldToTranslate]
                );
            }
        }

        foreach ($mappings['toUpper'] as $fieldToBeUpperred) {
            if (!empty($holding[$fieldToBeUpperred])) {
                $holding[$fieldToBeUpperred] = strtoupper(
                    $holding[$fieldToBeUpperred]
                );
            }
        }
        $holding['id'] = $this->getUniqueID();
        $holding['source'] = $this->getSourceId();
        // If is Aleph ..
        $ilsConfig = $this->getIlsConfig();
        if (isset($ilsConfig['Drivers'][$holding['source']])
            && $ilsConfig['Drivers'][$holding['source']] === 'Aleph'
        ) {
            // If we have all we need
            if (isset($holding['sequence_no']) && isset($holding['item_id'])
                && isset($holding['agency_id'])
            ) {
                $holding['item_id'] = $holding['agency_id'] . $holding['item_id']
                    . $holding['sequence_no'];
                unset($holding['agency_id']);
            }
        }
        $holding['w_id'] = $field['w'] ?? null;
        $holding['sigla'] = $field['e'] ?? null;
        return $holding;
    }

    /**
     * Filter items which we do not wan't to show
     * @param array $fields
     *
     * @return array
     */
    protected function filterOutRestrictedItems(array $fields): array
    {
        $mappings = $this->get996Mappings();
        if (isset($mappings['restricted'])) {
            $restrictions = $mappings['restricted'];
            $fields = array_filter($fields, function($f) use ($restrictions) {
                return !$this->isRestricted($f, $restrictions);
            });
        }
        return $fields;
    }

    /**
     * Returns mapping for 996 subfield according ta current source
     *
     * @return array with keys:
     *               toUpper - field to be uppered
     *               translate - fields to be translate (possibly with prefix)
     *               restricted - hidden fields
     *               ignoredVals - hidden field values
     *               data - actual data mappings
     */
    protected function get996Mappings(): array
    {
        $defaultMappings = $this->getDefault996Mappings();
        $overridenMappings = $this->getOverriden996Mappings();
        $mergedMappings = array_merge($defaultMappings, $overridenMappings);

        // special (translate, toUpper, restricted, ignoredVals) are arrays in config
        $mappings = array_filter($mergedMappings, function($mapping) {
            return is_array($mapping);
        });

        $data = array_filter($mergedMappings, function($mapping) {
            return !is_array($mapping);
        });
        $mappings['data'] = $data;

        $ignored = $mappings['ignoredVals'] ?? [];
        $mappings['ignoredVals'] = array_map(function($item) {
            return array_map('trim', explode(',', $item));
        }, $ignored);

        $translate = $mappings['translate'] ?? [];
        $translateMapping = [];
        foreach ($translate as $t) {
            list($field, $prefix) = explode(':', $t, 2);
            $translateMapping[$field] = $prefix ?? '';
        }
        $mappings['translate'] = $translateMapping;

        return $mappings;
    }

    /**
     * Returns array of config to process 996 mappings with.
     *
     * Returns null if not found.
     *
     * @return array
     */
    protected function getDefault996Mappings(): array
    {
        $ilsConfig = $this->getIlsConfig();
        return $ilsConfig['Default996Mappings'] ?? [];
    }

    /**
     * Returns array of config with which it is desired to override the default one.
     *
     * @return array
     */
    protected function getOverriden996Mappings(): array
    {
        $source = $this->getSourceId();
        $ilsConfig = $this->getIlsConfig();
        $overriden996Mappings = $ilsConfig['Overriden996Mappings'];
        if (isset($overriden996Mappings[$source])) {
            return $ilsConfig[$overriden996Mappings[$source]] ?? [];
        }
        return [];
    }

    /**
     * Returns true only if in $subfields is found key->value pair identical
     * with any key->value pair in restrictions.
     *
     * @param array $subfields
     * @param array $restrictions
     * @return boolean
     */
    protected function isRestricted($subfields, $restrictions): bool
    {
        $result = false;
        $restrictions = $restrictions ?? [];
        foreach ($restrictions as $key => $restrictedValue) {
            if (isset($subfields[$key]) && $subfields[$key] === $restrictedValue) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Returns true only if $ignoredKeyValsPairs has any restriction on current key
     * and the restriction on current key has at least one value in it's array of
     * ignored values identical with passed $subfieldValue.
     *
     * Otherwise returns false.
     *
     * @param string $subfieldValue
     * @param string $subfieldKey
     * @param array $ignoredKeyValsPairs
     * @return boolean
     */
    protected function isIgnored($subfieldValue, $subfieldKey, $ignoredKeyValsPairs): bool
    {
        $result = false;
        if (isset($ignoredKeyValsPairs[$subfieldKey])) {
            $result = array_search($subfieldValue, $ignoredKeyValsPairs[$subfieldKey])
                !== false;
        }
        return $result;
    }

    /**
     * There are rules how to sort holdings in some special cases.
     * Set $this->sortFields.
     *
     * @param array $fields
     * @return array
     */
    private function sortFields($fields): array
    {
        if ((isset($this->fields['format_display_mv'][0]))
            && ($this->fields['format_display_mv'][0] === '0/PERIODICALS/')) {
            usort($fields, [$this, 'sortLogic']);
        }
        return $fields;
    }

    /**
     * The comparison function for usort, must return an integer <, =, or > than 0 if the first
     * argument is <, =, or > than the second argument.
     *
     * Uses array $this->sortFields Subfields from 996, used to sorting.
     *
     * @param string $a
     * @param string $b
     *
     * @return integer
     */
    private function sortLogic($a, $b)
    {
        $found = false;
        $first = $second = '';
        foreach ($this->sortFields as $sort) {
            $a[$sort] = $a[$sort] ?? '';
            $b[$sort] = $b[$sort] ?? '';
            if ($a[$sort] != $b[$sort]) {
                $pattern = '/(\d+)(.+)?/';
                $first = preg_replace($pattern, '$1', $a[$sort]);
                $second = preg_replace($pattern, '$1', $b[$sort]);
                $found = true;
                break;
            }
        }
        $ret = $first < $second;
        return $found ? $ret : false;
    }
}