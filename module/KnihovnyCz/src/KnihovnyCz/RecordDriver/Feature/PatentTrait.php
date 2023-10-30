<?php

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
            'd' => 'publish_date',
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
