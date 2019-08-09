<?php
/**
 * Knihovny.cz solr marc record driver
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

class SolrMarc extends \VuFind\RecordDriver\SolrMarc
{

    /**
     * These Solr fields should be used for snippets if available (listed in order
     * of preference).
     *
     * @var array
     */
    protected $preferredSnippetFields = [
        'toc_txt_mv', 'fulltext'
    ];

    /**
     * These Solr fields should NEVER be used for snippets.  (We exclude author
     * and title because they are already covered by displayed fields; we exclude
     * spelling because it contains lots of fields jammed together and may cause
     * glitchy output; we exclude ID because random numbers are not helpful).
     *
     * @var array
     */
    protected $forbiddenSnippetFields = [
        'author', 'author_autocomplete', 'author_display', 'author_facet_str_mv',
        'author-letter', 'author_search', 'author_sort_str', 'author_str_mv',
        'authorCorporation_search_txt_mv', 'ctrlnum', 'id','publishDate',
        'source_title_facet_str', 'sourceTitle_search_txt_mv', 'spelling',
        'spellingShingle',  'title',  'title_auth', 'title_autocomplete',
        'title_display',  'title_full', 'title_fullStr',
        'titleSeries_search_txt_mv', 'title_short', 'title_sort', 'title_sub',
    ];

    protected $reverse = false;
    protected $sortFields = array();

    /**
     * Get patent info for export in txt
     * TODO: Do we really need these two methods? If so, shouldn it be renedered in template?
     */
    public function getPatentInfo() {
        $patentInfo = [];
        $patentInfo['country'] = $this->getFieldArray('013', array('b'))[0];
        $patentInfo['type'] = $this->getFieldArray('013', array('c'))[0];
        $patentInfo['id'] = $this->getFieldArray('013', array('a'))[0];
        $patentInfo['publish_date'] = $this->getFieldArray('013', array('d'))[0];
        if(empty($patentInfo)) {
            return false;
        }
        $patentInfoText = $this->renderPatentInfo($patentInfo);
        return $patentInfoText;
    }

    /**
     * Render patent info to export file
     *
     * @param $patentInfo array with patent info
     * @return string rendered string
     */
    public function renderPatentInfo($patentInfo) {
        $patentInfoText = '';
        $patentInfoText .= $this->translate('Patent') . ': ' . $patentInfo['country'] . ', ';
        switch ($patentInfo['type']) {
        case 'B6':
            $patentInfoText .= $this->translate('patent_file'); break;
        case 'A3':
            $patentInfoText .= $this->translate('app_invention'); break;
        case 'U1':
            $patentInfoText .= $this->translate('utility_model'); break;
        default:
            $patentInfoText .= $this->translate('unknown_patent_type'); break;
        }
        $patentInfoText .= ', ' . $patentInfo['id'] . ', ' . $patentInfo['publish_date'] . "\r\n";
        return $patentInfoText;
    }

    /**
     * Used in ajax to get sfx url
     */
    public function getChildrenIds()
    {
        return $this->fields['local_ids_str_mv'] ?? [];
    }

    public function getSourceId()
    {
        list ($source, $localId) = explode('.', $this->getUniqueID());
        return $source;
    }

    public function get866()
    {
        $field866 = $this->getFieldArray('866', array('s', 'x'));
        return $field866;
    }
    /**
     * Returns data from SOLR representing links and metadata to access SFX
     *
     * @return  array
     */
    public function get866Data()
    {
        return $this->fields['sfx_links'] ?? [];
    }

    /**
     * Get the publishers of the record.
     *
     * @return array
     */
    public function getPublishers()
    {
        return $this->fields['publisher_display_mv'] ?? [];
    }

    public function getFormats()
    {
        return $this->fields['format_display_mv'] ?? [];
    }

    /**
     * Returns perent record ID from SOLR
     *
     * @return  string
     */
    public function getParentRecordID()
    {
        return $this->fields['parent_id_str'] ?? [];
    }

    /**
     * Returns first of ISSNs, ISBNs and ISMNs from SOLR
     *
     * @return  string
     */
    public function getIsn()
    {
        return $this->fields['isbn'][0]
            ?? $this->fields['issn'][0]
            ?? $this->fields['ismn_int_mv'][0]
            ?? false;
    }

    /**
     * Get text that can be displayed to represent this record in
     * breadcrumbs.
     *
     * @return string Breadcrumb text to represent this record.
     */
    public function getBreadcrumb()
    {
        return $this->getTitle();
    }

    /**
     * Get the full title of the record.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->fields['title_display'] ?? '';
    }

    public function getSubtitle()
    {
        return $this->fields['title_sub_display'] ?? '';
    }

    public function getCitationRecordType()
    {
        return $this->fields['citation_record_type_str'] ?? '';
    }

    /**
     * Pick one line from the highlighted text (if any) to use as a snippet.
     *
     * @return mixed False if no snippet found, otherwise associative array
     * with 'snippet' and 'caption' keys.
     */
    public function getHighlightedSnippet()
    {
        // Only process snippets if the setting is enabled:
        if ($this->snippet) {
            // First check for preferred fields:
            foreach ($this->preferredSnippetFields as $current) {
                if (isset($this->highlightDetails[$current][0])) {
                    return [
                        'snippet' => $this->highlightDetails[$current][0],
                        'caption' => $this->getSnippetCaption($current)
                    ];
                }
            }
        }
        // If we got this far, no snippet was found:
        return false;
    }

    /**
     * Get authority ID of main author.
     *
     * @return string
     */
    public function getMainAuthorAuthorityRecordId()
    {
        return $this->fields['author_authority_id_display'] ?? false;
    }

    /**
     * Returns name of the Author to display
     *
     * @deprecated Used in ajax controller, should be used getPrimaryAuthor at call
     * @return string|NULL
     */
    public function getDisplayAuthor()
    {
        return $this->getPrimaryAuthors()[0];
    }

    /**
     * Get the main author of the record.
     *
     * @return array
     */
    public function getPrimaryAuthors()
    {
        if (isset($this->fields['author_display'])) {
            return [$this->fields['author_display']];
        }
        return [];
    }

    /**
     * Get an array of all secondary authors (complementing getPrimaryAuthor()).
     *
     * @return array
     */
    public function getSecondaryAuthors()
    {
        return $this->fields['author2_display_mv'] ?? [];
    }

    /**
     * Get the corporate author of the record.
     *
     * @return array
     */
    public function getCorporateAuthors()
    {
        if (isset($this->fields['corp_author_display'])) {
            return [$this->fields['corp_author_display']];
        }
        return [];
    }

    /**
     * Get an array of authority Ids of all secondary authors in the same order
     * and amount as getSecondaryAuthors() method from author2 solr field.
     *
     * @return array
     */
    public function getSecondaryAuthoritiesRecordIds()
    {
        return $this->fields['author2_authority_id_display_mv'] ?? [];
    }

    /**
     * Get the publication dates of the record.  See also getDateSpan().
     *
     * @return array
     */
    public function getPublicationDates()
    {
        return $this->fields['publishDate_display'] ?? [];
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISBNs()
    {
        // If ISBN is in the index, it should automatically be an array... but if
        // it's not set at all, we should normalize the value to an empty array.
        return (isset($this->fields['isbn_display_mv'])
            && is_array($this->fields['isbn_display_mv']))
            ? $this->fields['isbn_display_mv'] : [];
    }

    public function getISSNFromMarc()
    {
        $issn = $this->getFieldArray('022', array('a'));
        return $issn;
    }

    public function getScales()
    {
        $scales = $this->getFieldArray('255', array('a'));
        return $scales;
    }

    public function getMpts()
    {
        $field024s = $this->getFieldArray('024', array('a', '2'), false); // Mezinárodní patentové třídění
        $mpts = [];
        $count = count($field024s);
        if ($count) {
            for ($i = 0; $i < $count; $i++) {
                if (isset($field024s[$i+1])) {
                    if ($field024s[$i+1] == 'MPT') {
                        $mpts[] = $field024s[$i];
                    }
                }
            }
        }
        return $mpts;
    }

    /**
     * Get handler for related
     *
     * @return array
     */
    public function getFilterParamsForRelated()
    {
        return ['handler' => 'morelikethis'];
    }

    /**
     * Get an array of summary strings for the record.
     *
     * @return array
     */
    public function getSummary()
    {
        return $this->fields['summary_display_mv'] ?? [];
    }

    public function getMonographicSeries($searchAlsoInParentRecord = true)
    {
        $series = $this->fields['monographic_series_display_mv'] ?: false;
        if (! $series && $searchAlsoInParentRecord) {
            $series = $this->getParentRecordDriver()->getMonographicSeries(false);
        }
        return $series;
    }
    public function getMonographicSeriesUrl(string $serie)
    {
        $mainSerie = explode("|", $serie)[0];
        return '/Search/Results?lookfor0[]=' . urlencode($mainSerie)
            . '&amp;type0[]=adv_search_monographic_series&amp;join=AND&amp;searchTypeTemplate=advanced&amp;page=1&amp;bool0[]=AND';
    }
    public function getMonographicSeriesTitle(string $serie)
    {
        return implode(" | ", explode("|", $serie));
    }

    public function getZiskejBoolean() : bool
    {
        return $this->fields['ziskej_boolean'] ?? false;
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
                if (isset($this->getILSconfig()['Drivers'][$source]) && $this->getILSconfig()['Drivers'][$source] === 'Aleph') {
                    // If we have all we need
                    if (isset($holding['sequence_no']) && isset($holding['item_id']) && isset($holding['agency_id'])) {
                        $holding['item_id'] = $holding['agency_id'] . $holding['item_id'] . $holding['sequence_no'];
                        unset($holding['agency_id']);
                    } else {
                        // We actually cannot process Aleph holdings without complete item id ..
                        unset($holding['item_id']);
                    }
                }
                $holding['w_id'] = array_key_exists('w', $currentField) ? $currentField['w'] : null;
                $holding['sigla'] = array_key_exists('e', $currentField) ? $currentField['e'] : null;
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
     * Uses @param boolean $this->reverse Reverse the result.
     *
     * @param $a, $b
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

