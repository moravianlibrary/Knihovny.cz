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
            $subfields['ind1'] = $fieldData['i1'];
            $subfields['ind2'] = $fieldData['i2'];
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
     * Get an array of all ISSNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISSNs(): array
    {
        return (isset($this->fields['issn_display_mv'])
            && is_array($this->fields['issn_display_mv']))
            ? $this->fields['issn_display_mv'] : [];
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
     * Get cast from field 382
     *
     * @return array
     */
    public function getCast382(): array
    {
        $fields382 = $this->getMarcReader()->getFields('382');
        $lines = [];
        foreach ($fields382 as $field) {
            $line = '';
            foreach ($field['subfields'] as $subfield) {
                if (!empty($line) && ($subfield['code'] === 'a' || $subfield['code'] === 'b')) {
                    $line .= ' ; ';
                }
                if ($subfield['code'] === 'a' || $subfield['code'] === 'p') {
                    $line .= $subfield['data'];
                }
                if ($subfield['code'] === 'b') {
                    $line .= $this->translate('medium_solo') . ': ' . $subfield['data'];
                }
                if ($subfield['code'] === 'd') {
                    $line .= ' + ' . $this->translate('medium_doubling') . ': ' . $subfield['data'];
                }
                if ($subfield['code'] === 'n' || $subfield['code'] === 'e' || $subfield['code'] === 'v') {
                    $line .= ' (' . $subfield['data'] . ')';
                }
                if ($subfield['code'] === 's') {
                    $line .= ' ; [' . $this->translate('medium_total') . ': ' . $subfield['data'] . ']';
                }
            }
            $lines[] = $line;
        }
        return $lines;
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
        return $this->getFieldArray('510', ['a', 'c'], true);
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

    /**
     * Get notice for holdings availability in special cases - for now it is only applicable to historical fonds in MZK
     *
     * @return string
     * @throws \Exception
     */
    public function getHoldingsNotice(): string
    {
        $source = $this->getSourceId();
        $base = $this->getBase();

        $normsDocumentType = '0/NORMS/';
        if ($source === 'unmz' && $this->checkFormat($normsDocumentType)) {
            return 'holdings_notice_norms';
        }

        if ($source === 'mzk' && $base === 'MZK03') {
            $field991k = $this->getFirstFieldValue('991', ['k']);
            $field991Mapping = [
                'broumov' => 'holdings_notice_benediktini_broumov',
                'minorite' => 'holdings_notice_minorite_brno',
                'rajhrad' => 'holdings_notice_benediktini_rajhrad',
                'trebova' => 'holdings_notice_frantiskani_trebova',
            ];

            return $field991Mapping[$field991k] ?? '';
        }

        $items = $this->tryMethod('getOfflineHoldings');
        if ($source === 'mzk' && empty($items)) {
            return 'holdings_notice_no_items';
        }

        return '';
    }

    /**
     * Parse tag specification with indicators. Non-numeric indicators are
     * replaced with null.
     *
     * @param string $fieldSpec tag spec (eg. 245, 245## or 24577)
     *
     * @return array with tag, first and second indicator
     */
    public function parseTagSpecWithIndicators(string $fieldSpec): array
    {
        $field = substr($fieldSpec, 0, 3);
        $ind1 = null;
        $ind2 = null;
        if (strlen($fieldSpec) > 4) {
            $ind1 = substr($fieldSpec, 3, 1);
            $ind1 = \IntlChar::isdigit($ind1) ? $ind1 : null;
            $ind2 = substr($fieldSpec, 4, 1);
            $ind2 = \IntlChar::isdigit($ind2) ? $ind2 : null;
        }
        return [$field, $ind1, $ind2];
    }

    /**
     * Get book call number from marc fields
     *
     * @return string|null
     * @throws \Exception
     */
    public function getMarcCallNumber(): ?string
    {
        $source = $this->getSourceId();
        $base = $this->getBase();

        if ($source === 'mzk' && $base === 'MZK03') {
            $field991 = $this->getStructuredDataFieldArray('991');

            if (isset($field991[0]['k']) && $field991[0]['k'] === 'mzk') {
                $field910 = $this->getStructuredDataFieldArray('910');
                $parts = array_filter([
                    $field910[0]['b'] ?? null,
                    $field910[0]['r'] ?? null,
                    $field910[0]['s'] ?? null,
                ]);

                return implode(', ', $parts);
            }
        }

        return null;
    }
}
