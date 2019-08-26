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

class SolrMarc extends \KnihovnyCz\RecordDriver\SolrDefault
{
    use \VuFind\RecordDriver\IlsAwareTrait;
    use \VuFind\RecordDriver\MarcReaderTrait;
    use \VuFind\RecordDriver\MarcAdvancedTrait;
    use MarcField996AwareTrait;

    /**
     * Get patent info for export in txt
     * TODO: Do we really need these two methods? If so, shouldn't it be rendered in template?
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
}

