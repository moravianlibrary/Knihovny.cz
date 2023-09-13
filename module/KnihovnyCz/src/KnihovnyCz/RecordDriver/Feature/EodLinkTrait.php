<?php

/**
 * Trait EodLinkTrait
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2023.
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
 * @package  KnihovnyCz\RecordDriver
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\RecordDriver\Feature;

use function in_array;
use function is_array;
use function strlen;

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
