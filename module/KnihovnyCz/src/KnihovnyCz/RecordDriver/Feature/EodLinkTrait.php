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
        if (!isset($this->mainConfig->EedFormIds) || !isset($this->mainConfig->Eod->formLink)) {
            return null;
        }
        $eodFormIds = $this->mainConfig->EedFormIds->toArray();
        [$source, $recordId] = explode('.', $this->getUniqueID());
        $formIds = $eodFormIds[$source] ?? null;
        $formId = null;
        if (is_array($formIds)) {
            foreach ($formIds as $prefix => $id) {
                if (str_starts_with($recordId, $prefix)) {
                    $formId = $id;
                    $recordId = substr($recordId, strlen($prefix));
                    break;
                }
            }
        } else {
            $formId = $formIds;
        }
        if ($formId == null) {
            return null;
        }
        $lang = $this->getTranslatorLocale();
        $baseLink = $this->mainConfig->Eod->formLink;
        $separator = str_contains($baseLink, '?') ? '&' : '?';
        return $baseLink . $separator . http_build_query([
            'formular_id' => $formId,
            'sys_id' => $recordId,
            'lang' => $lang,
        ]);
    }
}
