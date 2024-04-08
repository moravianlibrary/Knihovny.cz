<?php

namespace KnihovnyCz\RecordDriver;

/**
 * Class solr marc record driver
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://github.com/moravianlibrary/Knihovny.cz Knihovny.cz
 */
class SolrMarc extends SolrDefault
{
    use \VuFind\RecordDriver\Feature\IlsAwareTrait;
    use \VuFind\RecordDriver\Feature\MarcReaderTrait;
    use \VuFind\RecordDriver\Feature\MarcAdvancedTrait {
        getCleanNBN as getCleanNBNMarc;
    }
    use Feature\BibframeTrait;
    use Feature\PatentTrait;

    /**
     * ISSN from marc record
     *
     * @return array
     */
    public function getISSNFromMarc()
    {
        $issn = $this->getFieldArray('022', ['a']);
        return $issn;
    }

    /**
     * Cartographic material scale from marc record
     *
     * @return array
     */
    public function getScales()
    {
        $scales = $this->getFieldArray('255', ['a']);
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
     * @return array
     */
    public function getRange()
    {
        return $this->getFieldArray('300');
    }

    /**
     * Non-standard ISBNs from marc record
     *
     * @return array
     */
    public function getNonStandardISBN()
    {
        return $this->getFieldArray('902');
    }

    /**
     * Get field and its subfields as array
     *
     * @param string $field Field tag
     *
     * @return array
     */
    protected function getStructuredDataFieldArray($field)
    {
        $result = [];
        foreach ($this->getMarcReader()->getFields($field) as $fieldData) {
            $subfields = [];
            foreach ($fieldData['subfields'] as $subfield) {
                $subfields[$subfield['code']] = trim($subfield['data']);
            }
            $result[] = $subfields;
        }
        return $result;
    }

    /**
     * Marc field 773
     *
     * @return array
     */
    public function getField773()
    {
        return $this->getStructuredDataFieldArray('773');
    }

    /**
     * Marc field 770
     *
     * @return array
     */
    public function getField770()
    {
        return $this->getStructuredDataFieldArray('770');
    }

    /**
     * Marc field 772
     *
     * @return array
     */
    public function getField772()
    {
        return $this->getStructuredDataFieldArray('772');
    }

    /**
     * Marc field 777
     *
     * @return array
     */
    public function getField777()
    {
        return $this->getStructuredDataFieldArray('777');
    }

    /**
     * Marc field 780
     *
     * @return array
     */
    public function getField780()
    {
        return $this->getStructuredDataFieldArray('780');
    }

    /**
     * Marc field 785
     *
     * @return array
     */
    public function getField785()
    {
        return $this->getStructuredDataFieldArray('785');
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
        // This is needed to overcome getSummary method defined in MarcAdvancedTrait
        return parent::getSummary();
    }

    /**
     * Get all item identifiers for this record
     *
     * @return array
     */
    public function getItemIds(): array
    {
        return $this->getFieldArray('996', ['t'], false);
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getURLs()
    {
        return [];
    }

    /**
     * Returns true if the record supports real-time AJAX status lookups.
     *
     * @return bool
     */
    public function supportsAjaxStatus(): bool
    {
        // call parent method instead of implementation from IlsAwareTrait
        return parent::supportsAjaxStatus();
    }

    /**
     * Get an array of physical descriptions of the item.
     *
     * @return array
     */
    public function getPhysicalDescriptions()
    {
        return $this->getFieldArray('300', ['a', 'b', 'c', 'e', 'f', 'g'], true);
    }

    /**
     * Get just the first listed national bibliography number (or false if none
     * available).
     *
     * @return mixed
     */
    public function getCleanNBN()
    {
        $nbn = $this->getCleanNBNMarc();
        if (
            empty($nbn) && $this->ils != null
            && $sigla = $this->ils->sourceToSigla($this->getSourceId())
        ) {
            $sigla = strtolower($this->remapSiglaForNkp($sigla));
            $nbn = ['nbn' => $sigla . '-' . $this->getLocalIdForObalkyKnih()];
        }
        return $nbn;
    }

    /**
     * Get reviews
     *
     * @return array
     */
    public function getReviews(): array
    {
        $fields = $this->getMarcReader()->getFields('787');
        $reviews = [];
        foreach ($fields as $field) {
            if ($field['i1'] != '0') {
                continue;
            }
            $sf = [];
            foreach ($field['subfields'] as $subfield) {
                $sf[$subfield['code']] = $subfield['data'];
            }
            if (
                isset($sf['i']) && trim($sf['i']) == 'Recenze na:'
                && isset($sf['t']) && isset($sf['d'])
            ) {
                $reviews[] = (isset($sf['a']) ? $sf['a'] . '. ' : '') .
                    $sf['t'] . '. -- ' . $sf['d'];
            }
        }
        return $reviews;
    }

    /**
     * Get identifier stripped of all prefixes
     *
     * @return string
     */
    protected function getLocalIdForObalkyKnih(): string
    {
        $sigla = strtolower($this->ils->sourceToSigla($this->getSourceId()));
        [, $id] = explode('.', $this->getUniqueID(), 2);
        // NKP, NLK, MZK, ARL and Verbis libraries use 001 as identifiers in obalky knih
        if (
            $sigla === 'aba001'
            || $sigla === 'aba008'
            || $sigla === 'boa001'
            || $this->isArl()
            || $this->isVerbis()
        ) {
            return strtolower($this->getIdFrom001());
        }
        if ($this->isAleph() && str_contains($id, '-')) {
            [$id] = explode(' ', $id);
            [, $id] = explode('-', $id);
            $id = trim($id);
            if ($sigla === 'ola001') {
                return 'vkol' . $id;
            }
            return $id;
        }
        return $id;
    }

    /**
     * Is record from library with Aleph ILS?
     *
     * @return bool
     */
    protected function isAleph(): bool
    {
        return $this->hasILS()
            && is_subclass_of($this->ils->getDriverName($this->getUniqueID()), '\VuFind\ILS\Driver\Aleph');
    }

    /**
     * Is record from library with ARL ILS?
     *
     * @return bool
     */
    protected function isArl(): bool
    {
        return str_contains($this->getUniqueID(), 'UsCat*');
    }

    /**
     * Is record from library with Verbis ILS?
     *
     * @return bool
     */
    protected function isVerbis(): bool
    {
        return 'verbis' == strtolower($this->ils->getIlsType($this->getUniqueID()));
    }

    /**
     * As we work with all NKP departments as it would be one library, we need to
     * remap some bases to the correct sigla
     *
     * @param string $sigla Sigla
     *
     * @return string
     */
    protected function remapSiglaForNkp(string $sigla): string
    {
        if ($sigla !== 'aba001') {
            return $sigla;
        }
        [, $id] = explode('.', $this->getUniqueID(), 2);
        $base = substr($id, 0, 5);
        return match ($base) {
            'KKL01' => 'aba003',
            'SLK01' => 'aba004',
            'STT01' => 'aba018',
            default => 'aba001',
        };
    }

    /**
     * Get citation (parsed from field 773)
     *
     * @return string|null
     */
    public function getFieldCitation(): ?string
    {
        return trim(
            implode(
                ', ',
                array_map(
                    function ($a): string {
                        return implode(', ', $a);
                    },
                    $this->getField773()
                )
            )
        );
    }

    /**
     * Get ISSN (parsed from field 773)
     *
     * @return string|null
     */
    public function getIssnFromField773(): ?string
    {
        $f773 = $this->getField773();
        return isset($f773[0]['x']) ? trim((string)$f773[0]['x']) : null;
    }

    /**
     * Get ISSN field
     *
     * @return string|null
     *
     * @throws \Exception
     */
    public function getISSN(): ?string
    {
        $field = 'issn';

        $array = [];

        if (isset($this->fields[$field])) {
            $array = $this->fields[$field];
        }

        $parent = $this->getParentRecord();
        if ($parent !== null && isset($parent->fields[$field])) {
            $array = $parent->fields[$field];
        }

        return $array[0] ?? $this->getIssnFromField773();
    }

    /**
     * Get year (parsed from field 773)
     *
     * @return string|null
     */
    public function getYearFromField773(): ?string
    {
        $f773 = $this->getField773();
        return isset($f773[0][9]) ? trim((string)$f773[0][9]) : null;
    }

    /**
     * Get periodical volume (parsed from field 773)
     *
     * @return string|null
     */
    public function getPYearFromField773(): ?string
    {
        $f773 = $this->getField773();

        if (isset($f773[0]['q'])) {
            $f = trim((string)$f773[0]['q']);
            if (preg_match('/(\d+):(\d+)/', $f)) {
                [$volume, $issue] = explode(':', $f);
                return $volume;
            }
        }

        return null;
    }

    /**
     * Get periodical issue (parsed from field 773)
     *
     * @return string|null
     */
    public function getPNumberFromField773(): ?string
    {
        $f773 = $this->getField773();

        if (isset($f773[0]['q'])) {
            $f = trim((string)$f773[0]['q']);
            if (preg_match('/(\d+):(\d+)/', $f)) {
                [$volume, $issue] = explode(':', $f);
                return $issue;
            }
        }

        return null;
    }

    /**
     * Get page start (parsed from field 773)
     *
     * @return string|null
     */
    public function parsePageStartFromField773(): ?string
    {
        $f773 = $this->getField773();
        if (isset($f773[0]['g'])) {
            if (preg_match('/(s\.)\s(\d+)-(\d+)/', $f773[0]['g'], $matches)) {
                return $matches[2] ?? null;
            }
        }

        return null;
    }

    /**
     * Get page end (parsed from field 773)
     *
     * @return string|null
     */
    public function parsePageEndFromField773(): ?string
    {
        $f773 = $this->getField773();
        if (isset($f773[0]['g'])) {
            if (preg_match('/(s\.)\s(\d+)-(\d+)/', $f773[0]['g'], $matches)) {
                return $matches[3] ?? null;
            }
        }

        return null;
    }

    /**
     * Get CNB number
     *
     * @return string|null
     */
    public function getCnb(): ?string
    {
        $nbn = $this->getCleanNBNMarc();
        return $nbn['nbn'] ?? null;
    }

    /**
     * Formatted Contents Note
     *
     * @return array
     */
    public function getNote505(): array
    {
        return $this->getFieldArray('505', ['a'], false);
    }

    /**
     * Citation/References Note
     *
     * @return array
     */
    public function getNote510(): array
    {
        return $this->getFieldArray('510', ['a'], false);
    }

    /**
     * Ownership and Custodial History
     *
     * @return array
     */
    public function getNote561(): array
    {
        return $this->getFieldArray('561', ['a'], false);
    }

    /**
     * Binding Information
     *
     * @return array
     */
    public function getNote563(): array
    {
        return $this->getFieldArray('563', ['a'], false);
    }

    /**
     * Defect note
     *
     * @return array
     */
    public function getNote590(): array
    {
        return $this->getFieldArray('590', ['a'], false);
    }

    /** Get notice for holdings availability in special cases - for now it is only applicable to historical fonds in MZK
     *
     * @return string
     * @throws \Exception
     */
    public function getHoldingsNotice(): string
    {
        [$source, $id] = explode('.', $this->getUniqueID());
        [$base] = explode('-', $id);
        if ($source !== 'mzk' || $base !== 'MZK03') {
            return '';
        }

        $field991k = $this->getFirstFieldValue('991', ['k']);
        $field991Mapping = [
            'broumov' => 'holdings_notice_benediktini_broumov',
            'minorite' => 'holdings_notice_minorite_brno',
            'rajhrad' => 'holdings_notice_benediktini_rajhrad',
            'trebova' => 'holdings_notice_frantiskani_trebova',
        ];

        return $field991Mapping[$field991k] ?? '';
    }
}
