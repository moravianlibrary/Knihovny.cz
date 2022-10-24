<?php
declare(strict_types=1);

/**
 * Class Holds
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
 * @category Knihovny.cz
 * @package  KnihovnyCz\ILS\Logic
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\ILS\Logic;

/**
 * Class Holds
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ILS\Logic
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Holds extends \VuFind\ILS\Logic\Holds
{
    /**
     * Protected method for driver defined holdings
     *
     * @param array $result          A result set returned from a driver
     * @param array $holdConfig      Hold configuration from driver
     * @param bool  $requestsBlocked Are user requests blocked?
     *
     * @return array A sorted results set
     */
    protected function driverHoldings($result, $holdConfig, $requestsBlocked)
    {
        $holdings = [];

        if ($result['total']) {
            foreach ($result['holdings'] as $copy) {
                $show = !in_array($copy['location'], $this->hideHoldings);
                if ($show) {
                    if ($holdConfig) {
                        // Is this copy holdable / linkable
                        if (!$requestsBlocked
                            && ($copy['addLink'] ?? false)
                            && ($copy['is_holdable'] ?? true)
                        ) {
                            $action = ($copy['holdtype'] == 'shortloan')
                                ? 'ShortLoan' : 'Hold';
                            $copy['link'] = $this->getRequestDetails(
                                $copy,
                                $holdConfig['HMACKeys'],
                                $action
                            );
                            $copy['linkLightbox'] = true;
                            // If we are unsure whether hold options are available,
                            // set a flag so we can check later via AJAX:
                            $copy['check'] = $copy['addLink'] === 'check';
                        }
                    }

                    $groupKey = $this->getHoldingsGroupKey($copy);
                    $holdings[$groupKey][] = $copy;
                }
            }
        }
        return $holdings;
    }
}
