<?php

namespace KnihovnyCz\Controller;

/**
 * Class MyResearchTrait
 *
 * @category VuFind
 * @package  KnihovnyCz\Controllers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait MyResearchTrait
{
    protected static $EXCEPTIONS_TO_SHOW = [
        'VuFind\ILS\Driver\AlephRestfulException',
        'VuFind\Exception\ILS',
    ];

    /**
     * Show the exception in the flash messenger
     *
     * @param \Exception $ex exception to show
     *
     * @return void
     */
    protected function showException(\Exception $ex)
    {
        $message = 'ils_offline_home_message';
        if (in_array($ex::class, self::$EXCEPTIONS_TO_SHOW)) {
            $message = $ex->getMessage();
        }
        $this->flashMessenger()->addErrorMessage($message);
    }

    /**
     * Add details from offline holdings
     *
     * @param $resources resources
     *
     * @return void
     */
    protected function addDetailsFromOfflineHoldings(&$resources)
    {
        foreach ($resources as &$resource) {
            $ilsDetails = $resource->getExtraDetail('ils_details');
            if (!empty($ilsDetails['volume'] ?? null)) {
                continue;
            }
            if (isset($ilsDetails['description'])) {
                $ilsDetails['volume'] = $ilsDetails['description'];
            } elseif (isset($ilsDetails['hold_item_id'])) {
                $item = $resource->tryMethod(
                    'getOfflineHoldingByItemId',
                    [$ilsDetails['hold_item_id']],
                    []
                );
                $ilsDetails['volume'] = $item['description'] ?? null;
            } elseif (
                isset($ilsDetails['item_id'])
                && str_contains($ilsDetails['item_id'], '.')
            ) {
                [, $itemId] = explode('.', $ilsDetails['item_id']);
                $item = $resource->tryMethod(
                    'getOfflineHoldingByItemId',
                    [$itemId],
                    []
                );
                $ilsDetails['volume'] = $item['description'] ?? null;
            } elseif (isset($ilsDetails['barcode'])) {
                $item = $resource->tryMethod(
                    'getOfflineHoldingByBarcode',
                    [$ilsDetails['barcode']],
                    []
                );
                $ilsDetails['volume'] = $item['d'] ?? null;
            }
            $resource->setExtraDetail('ils_details', $ilsDetails);
        }
    }
}
