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
    use PatentTrait;

    public function getSourceId()
    {
        list ($source, $localId) = explode('.', $this->getUniqueID());
        return $source;
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

    /**
     * Get the edition of the current record.
     *
     * @return string
     */
    public function getEdition()
    {
        return $this->getFirstFieldValue('250', ['a']);
    }

    /**
     * Returns document range info from field 300
     *
     * @return  array
     */
    public function getRange()
    {
        return $this->getFieldArray('300');
    }

    public function getNonStandardISBN()
    {
        return $this->getFieldArray('902');
    }

    /*
     * @return array
     */
    protected function getStructuredDataFieldArray($field)
    {
        $result = [];
        $fieldsData = $this->getMarcRecord()->getFields($field);
        foreach ($fieldsData as $fieldObj) {
            $subfieldsData = $fieldObj->getSubfields();
            $subfieldsArray = iterator_to_array($subfieldsData);
            $result[] = array_map(function($part) {
                return $part->getData();
            }, $subfieldsArray);
        }
        return $result;
    }

    public function getField773()
    {
        return $this->getStructuredDataFieldArray("773");
    }

    public function getField770()
    {
        return $this->getStructuredDataFieldArray("770");
    }

    public function getField772()
    {
        return $this->getStructuredDataFieldArray("772");
    }

    public function getField777()
    {
        return $this->getStructuredDataFieldArray("777");
    }

    public function getField780()
    {
        return $this->getStructuredDataFieldArray("780");
    }

    public function getField785()
    {
        return $this->getStructuredDataFieldArray("785");
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

