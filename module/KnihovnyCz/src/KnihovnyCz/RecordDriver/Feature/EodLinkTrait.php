<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordDriver\Feature;

/**
 * Trait EodLinkTrait
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordDriver
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

trait EodLinkTrait
{
    /**
     * Get link to EOD form
     *
     * @return string|null
     */
    public function getEodLink(): ?string
    {
        $statuses = $this->fields['local_view_statuses_facet_mv'] ?? [];
        $isEOD = in_array('available_for_eod', $statuses);
        if (!$isEOD) {
            return null;
        }
        if (!isset($this->mainConfig->EodFormIds) || !isset($this->mainConfig->Eod->formLink)) {
            return null;
        }
        $eodFormIds = $this->mainConfig->EodFormIds->toArray();
        $formIds = $eodFormIds[$this->getSourceId()] ?? null;
        $formId = $formIds[$this->getBase()] ?? (is_string($formIds) ? $formIds : null);
        if ($formId === null) {
            return null;
        }
        $lang = $this->getTranslatorLocale();
        $baseLink = $this->mainConfig->Eod->formLink;
        $separator = str_contains($baseLink, '?') ? '&' : '?';
        return $baseLink . $separator . http_build_query([
            'formular_id' => $formId,
            'sys_id' => $this->getSysnoForEod(),
            'lang' => $lang,
        ]);
    }

    /**
     * Get the system number for EOD requests.
     *
     * @return string|null
     */
    protected function getSysnoForEod(): ?string
    {
        [$source, $recordId] = explode('.', $this->getUniqueID());
        if ($source === 'nkp') {
            // For NKP, we use the record ID directly as it is already in the correct format.
            return $this->getIdFrom001();
        }

        if (str_contains($recordId, '-')) {
            [, $sysno] = explode('-', $recordId, 2);
            return $sysno;
        }
        return $recordId;
    }
}
