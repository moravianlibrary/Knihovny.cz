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

//FIXME this really needs refactoring
trait MarcField996AwareTrait
{
    protected $reverse = false;
    protected $sortFields = array();
    protected $ilsConfig = null;

    protected function getILSconfig()
    {
        if ($this->ilsConfig === null) {
            $this->ilsConfig = $this->ils->getDriverConfig();
        }
        return $this->ilsConfig;
    }

    protected function getAll996Subfields()
    {
        $fields = [];
        $fieldsParsed = $this->getMarcRecord()->getFields('996');
        foreach ($fieldsParsed as $field) {
            $subfieldsParsed = $field->getSubfields();
            $subfields = [];
            foreach ($subfieldsParsed as $subfield) {
                $subfieldCode = trim($subfield->getCode());
                // If is this subfield already set, ignore next value .. probably incorrect OAI data
                if (! isset($subfields[$subfieldCode]))
                    $subfields[$subfieldCode] = $subfield->getData();
            }
            $fields[] = $subfields;
        }
        return $fields;
    }

    /**
     * Returns array of holdings parsed via indexed 996 fields.
     *
     * TODO: Implement filtering
     *
     * @param array $filters
     *
     * @return array
     */
    protected function parseHoldingsFrom996field($filters = [])
    {
        $id = $this->getUniqueID();
        $fields = $this->getAll996Subfields();
        $source = $this->getSourceId();
        $mappingsFor996 = $this->getMappingsFor996($source);
        // Remember to unset all arrays at that would log an error providing array as another's array key
        if (isset($mappingsFor996['restricted'])) {
            $restrictions = $mappingsFor996['restricted'];
            unset($mappingsFor996['restricted']);
        }
        if (isset($mappingsFor996['ignoredVals'])) {
            $ignoredKeyValsPairs = $mappingsFor996['ignoredVals'];
            unset($mappingsFor996['ignoredVals']);
            foreach ($ignoredKeyValsPairs as &$ignoredValue)
                $ignoredValue = array_map('trim', explode(',', $ignoredValue));
        }
        if (isset($mappingsFor996['toUpper'])) {
            $toUpper = $mappingsFor996['toUpper'];
            // We will take care of the upperation process in the closest iteration
            unset($mappingsFor996['toUpper']);
        } else {
            // We will iterate over this, so don't let it be null
            $toUpper = [];
        }
        $toTranslate = [];
        // Here particular fields translation configuration takes place (see comments in MultiBackend.ini)
        if (isset($mappingsFor996['translate'])) {
            $toTranslateArray = $mappingsFor996['translate'];
            unset($mappingsFor996['translate']);
            foreach ($toTranslateArray as $toTranslateElement) {
                $toTranslateElements = explode(':', $toTranslateElement);
                $fieldToTranslate = $toTranslateElements[0];
                if (count($toTranslateElements) < 2)
                    $prependString = '';
                else
                    $prependString = $toTranslateElements[1];
                $toTranslate[$fieldToTranslate] = $prependString;
            }
        }
        $this->sortFields($fields, $source);
        if ((isset($this->fields['format_display_mv'][0])) && ($this->fields['format_display_mv'][0] == '0/PERIODICALS/')) {
            usort($fields, function($a, $b) {
                $found = false;
                $sortFields = array('y', 'v', 'i');
                foreach ($sortFields as $sort) {
                    if (! isset($a[$sort])) {
                        $a[$sort] = '';
                    }
                    if (! isset($b[$sort])) {
                        $b[$sort] = '';
                    }
                    if ($a[$sort] != $b[$sort]) {
                        $pattern = '/(\d+)(.+)?/';
                        $first = preg_replace($pattern, '$1', $a[$sort]);
                        $second = preg_replace($pattern, '$1', $b[$sort]);
                        $found = true;
                        break;
                    }
                }
                return $found ? ($first < $second) : false;
            });
        }
        $holdings = [];
        foreach ($fields as $currentField) {
            if (! $this->shouldBeRestricted($currentField, $restrictions)) {
                unset($holding);
                $holding = array();
                foreach ($mappingsFor996 as $variableName => $current996Mapping) {
                    // Here it omits unset values & values, which are desired to be ignored by their presence in ignoredVals MultiBackend.ini's array
                    if (! empty($currentField[$current996Mapping]) && ! $this->isIgnored($currentField[$current996Mapping], $current996Mapping, $ignoredKeyValsPairs)) {
                        $holding[$variableName] = $currentField[$current996Mapping];
                    }
                }
                // Translation takes place from translate
                foreach ($toTranslate as $fieldToTranslate => $prependString) {
                    if (! empty($holding[$fieldToTranslate]))
                        $holding[$fieldToTranslate] = $this->translate($prependString . $holding[$fieldToTranslate], null, $holding[$fieldToTranslate]);
                }
                foreach ($toUpper as $fieldToBeUpperred) {
                    if (! empty($holding[$fieldToBeUpperred]))
                        $holding[$fieldToBeUpperred] = strtoupper($holding[$fieldToBeUpperred]);
                }
                $holding['id'] = $id;
                $holding['source'] = $source;
                // If is Aleph ..
                $ilsConfig = $this->getILSconfig();
                if (isset($ilsConfig['Drivers'][$source]) && $ilsConfig['Drivers'][$source] === 'Aleph') {
                    // If we have all we need
                    if (isset($holding['sequence_no']) && isset($holding['item_id']) && isset($holding['agency_id'])) {
                        $holding['item_id'] = $holding['agency_id'] . $holding['item_id'] . $holding['sequence_no'];
                        unset($holding['agency_id']);
                    } else {
                        // We actually cannot process Aleph holdings without complete item id ..
                        unset($holding['item_id']);
                    }
                }
                $holding['w_id'] = $currentField['w'] ?? null;
                $holding['sigla'] = $currentField['e'] ?? null;
                $holdings[] = $holding;
            }
        }
        return $holdings;
    }

    /**
     * Returns array of key->value pairs where the key is variableName &
     * value is mapped subfield.
     *
     * This method basically fetches default996Mappings & overrides there
     * these variableNames, which are present in overriden996mappings.
     *
     * For more info see method getOverriden996Mappings
     *
     * @param string $source
     *
     * @return mixed null | array
     */
    protected function getMappingsFor996($source)
    {
        $default996Mappings = $this->getDefault996Mappings();
        $overriden996Mappings = $this->getOverriden996Mappings($source);
        if ($overriden996Mappings === null)
            return $default996Mappings;
        // This will override all identical entries
        $merged = array_reverse(array_merge($default996Mappings, $overriden996Mappings));
        // We shouldn't set value where is the subfield the same as in any other overriden default variableName
        return $this->array_unique_with_nested_arrays($merged);
    }

    /**
     * Returns array of config to process 996 mappings with.
     *
     * Returns null if not found.
     *
     * @return mixed null | array
     */
    protected function getDefault996Mappings()
    {
        $ilsConfig = $this->getILSconfig();
        return isset($ilsConfig['Default996Mappings']) ? $ilsConfig['Default996Mappings'] : [];
    }

    /**
     * Returns true only if in $subfields is found key->value pair identical
     * with any key->value pair in restrictions.
     *
     * @param array $subfields
     * @param array $restrictions
     * @return boolean
     */
    protected function shouldBeRestricted($subfields, $restrictions)
    {
        if ($restrictions === null)
            return false;
        foreach ($restrictions as $key => $restrictedValue) {
            if (isset($subfields[$key]) && $subfields[$key] == $restrictedValue)
                return true;
        }
        return false;
    }

    /**
     * This function is similar to array_unique, but with support
     * for nested arrays to make sure no error occurs.
     *
     * @param array $mergedOnes
     * @return array
     *
     * FIXME: This is bad and should be removed - if we need something like this, we probably have bad design...
     */
    protected function array_unique_with_nested_arrays($mergedOnes)
    {
        $nestedArrays = [];
        foreach ($mergedOnes as $key => $value) {
            if ($value !== null && is_array($value)) {
                $nestedArrays[$key] = $value;
                unset($mergedOnes[$key]);
            }
        }
        $toReturn = array_unique($mergedOnes);
        foreach ($nestedArrays as $key => $value) {
            // We won't do callback here as e.g. array 'restricted' may have multiple key-value pair with duplicate values
            $toReturn[$key] = $value;
        }
        return $toReturn;
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
    protected function isIgnored($subfieldValue, $subfieldKey, $ignoredKeyValsPairs)
    {
        if ($ignoredKeyValsPairs === null)
            return false;
        if (isset($ignoredKeyValsPairs[$subfieldKey]))
            return array_search($subfieldValue, $ignoredKeyValsPairs[$subfieldKey]) !== false;
        return false;
    }

    /**
     * There are rules how to sort holdings in some special cases.
     * Set $this->reverse and $this->sortFields.
     *
     * @param array $fields
     * @param string $source
     */
    private function sortFields(&$fields, $source) {
        if (($source == 'kfbz') &&
            (isset($this->fields['format_display_mv'][0])) &&
            ($this->fields['format_display_mv'][0] == '0/BOOKS/')) {
            $this->reverse = false;
            $this->sortFields = array('l',);
            usort($fields, array($this, 'sortLogic'));
        }
        if ((isset($this->fields['format_display_mv'][0])) && ($this->fields['format_display_mv'][0] == '0/PERIODICALS/')) {
            $this->reverse = true;
            $this->sortFields = array('y', 'v', 'i');
            usort($fields, array($this, 'sortLogic'));
        }
    }

    /**
     * The comparison function for usort, must return an integer <, =, or > than 0 if the first
     * argument is <, =, or > than the second argument.
     *
     * Uses array $this->sortFields Fields from 996, used to sorting.
     * Uses boolean $this->reverse Reverse the result
     *
     * @param $a
     * @param $b
     *
     * @return integer
     */
    private function sortLogic($a, $b) {
        $found = false;
        $first = $second = '';
        foreach ($this->sortFields as $sort) {
            if (! isset($a[$sort])) {
                $a[$sort] = '';
            }
            if (! isset($b[$sort])) {
                $b[$sort] = '';
            }
            if ($a[$sort] != $b[$sort]) {
                $pattern = '/(\d+)(.+)?/';
                $first = preg_replace($pattern, '$1', $a[$sort]);
                $second = preg_replace($pattern, '$1', $b[$sort]);
                $found = true;
                break;
            }
        }
        $ret = $this->reverse ? ($first < $second) : ($first > $second);
        return $found ? $ret : false;
    }
}