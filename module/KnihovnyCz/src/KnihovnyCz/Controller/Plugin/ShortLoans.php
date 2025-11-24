<?php

namespace KnihovnyCz\Controller\Plugin;

use VuFind\Validator\CsrfInterface;

/**
 * Support class for time slots
 *
 * @category VuFind
 * @package  Controller_Plugins
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ShortLoans extends \Laminas\Mvc\Controller\Plugin\AbstractPlugin
{
    /**
     * Process cancellation requests.
     *
     * @param array         $patron        Current logged in patron
     * @param CsrfInterface $csrfValidator CSRF validator
     *
     * @return array                          The result of the cancellation, an
     * associative array keyed by item ID (empty if no cancellations performed)
     */
    public function cancelShortLoans($patron, $csrfValidator = null)
    {
        $controller = $this->getController();
        if (!$controller) {
            return [];
        }

        // Retrieve the flashMessenger helper:
        $flashMsg = $controller->flashMessenger();
        $params = $controller->params();

        // Pick IDs to cancel based on which button was pressed:
        $all = $params->fromPost('cancelAll');
        $selected = $params->fromPost('cancelSelected');
        if (!empty($all)) {
            $details = $params->fromPost('cancelAllIDS');
        } elseif (!empty($selected)) {
            // Include cancelSelectedIDS for backwards-compatibility:
            $details = $params->fromPost('selectedIDS')
                ?? $params->fromPost('cancelSelectedIDS');
        } else {
            // No button pushed -- no action needed
            return [];
        }

        if (null !== $csrfValidator) {
            if (!$csrfValidator->isValid($params->fromPost('csrf'))) {
                $flashMsg->addErrorMessage('csrf_validation_failed');
                return [];
            }
            // After successful token verification, clear list to shrink session
            // and prevent double submit:
            $csrfValidator->trimTokenList(0);
        }
        if (empty($details)) {
            $flashMsg->addMessage('hold_empty_selection', 'error');
            return [];
        }

        $catalog = $controller->getIls();
        $cancelResults = $catalog->cancelShortLoans(
            ['details' => $details, 'patron' => $patron]
        );
        if ($cancelResults == false) {
            $flashMsg->addMessage('hold_cancel_fail', 'error');
        } else {
            $failed = 0;
            foreach ($cancelResults['items'] ?? [] as $item) {
                if (!$item['success']) {
                    ++$failed;
                }
            }
            if ($failed) {
                $msg = $controller->translate(
                    'hold_cancel_fail_items',
                    ['%%count%%' => $failed]
                );
                $flashMsg->addErrorMessage($msg);
            }
            if ($cancelResults['count'] > 0) {
                $msg = $controller->translate(
                    'hold_cancel_success_items',
                    ['%%count%%' => $cancelResults['count']]
                );
                $flashMsg->addSuccessMessage($msg);
            }
            return $cancelResults;
        }
        return [];
    }

    /**
     * Process place holds
     *
     * @param array         $patron        Current logged in patron
     * @param string        $recordId      Record id
     * @param string        $itemId        Item id
     * @param CsrfInterface $csrfValidator CSRF validator
     *
     * @return boolean                     Result
     */
    public function placeHolds($patron, $recordId, $itemId, $csrfValidator = null)
    {
        $controller = $this->getController();
        $catalog = $controller->getIls();
        $flashMsg = $controller->flashMessenger();
        $csrf = $controller->params()->fromPost('csrf', null);
        if (!$csrfValidator->isValid($csrf)) {
            $flashMsg->setNamespace('error')
                ->addMessage('csrf_validation_failed');
            return [];
        }
        $slots = $controller->params()->fromPost('slot', []);
        $numOfFailures = 0;
        foreach ($slots as $slot) {
            $details = [
                'patron' => $patron,
                'id' => $recordId,
                'item_id' => $itemId,
                'slot' => $slot,
            ];
            try {
                $result = $catalog->placeShortLoan($details);
                if (!$result['success']) {
                    $numOfFailures++;
                }
            } catch (\Exception $ex) {
                $numOfFailures++;
            }
        }
        if (empty($slots)) {
            $flashMsg->addErrorMessage('short_loan_no_slot_selected_error');
        } elseif ($numOfFailures == count($slots)) {
            $flashMsg->addErrorMessage('short_loan_request_error_text');
        } elseif ($numOfFailures > 0) {
            $msg = [
                'html' => true,
                'msg' => 'short_loan_request_partial_error_text',
                'tokens' => [
                    '%%url%%' => $this->getController()->url()->fromRoute('myresearch-shortloans'),
                ],
            ];
            $flashMsg->addSuccessMessage($msg);
        } else {
            $msg = [
                'html' => true,
                'msg' => 'short_loan_ok_text_html',
                'tokens' => [
                    '%%url%%' => $this->getController()->url()->fromRoute('myresearch-shortloans'),
                ],
            ];
            $flashMsg->addSuccessMessage($msg);
            return true;
        }
        return false;
    }

    /**
     * Update ILS details with cancellation-specific information, if appropriate.
     *
     * @param array $ilsDetails details from ILS driver's
     *                          getMyShortLoanRequests() method
     *
     * @return void
     */
    public function addCancelDetails(&$ilsDetails)
    {
        $catalog = $this->getController()->getIls();
        foreach ($ilsDetails as &$detail) {
            $detail['cancel_details'] = $catalog
                ->getCancelShortLoanDetails($detail);
        }
    }

    /**
     * Fill empty slots
     *
     * @param $slots slots to process
     *
     * @return array
     */
    public function fillSlots($slots)
    {
        $min = 24;
        $minTime = '24:00';
        $max = 0;
        $maxTime = '0:00';
        $slotsInHour = 2;
        foreach ($slots as $date => &$daySlots) {
            foreach ($daySlots as &$slot) {
                $start = $slot['start'] = $this->convertTime($slot['start_time']);
                $end = $slot['end'] = $this->convertTime($slot['end_time']);
                if ($start < $min) {
                    $min = $start;
                    $minTime = $slot['start_time'];
                }
                if ($max < $end) {
                    $max = $end;
                    $maxTime = $slot['end_time'];
                }
            }
            $this->sortSlots($daySlots);
        }
        // fill missing slots
        foreach ($slots as $date => &$daySlots) {
            $emptySlots = [];
            $prevSlot = null;
            foreach ($daySlots as &$slot) {
                if (
                    $prevSlot != null && $prevSlot['end_time'] != $slot['start_time']
                ) {
                    $emptySlots[] = $this->createEmptySlot(
                        $prevSlot['end_time'],
                        $slot['start_time']
                    );
                }
                $prevSlot = $slot;
            }
            $first = $daySlots[0];
            if ($first['start'] != $min) {
                $emptySlots[] = $this->createEmptySlot(
                    $minTime,
                    $first['start_time']
                );
            }
            $last = end($daySlots);
            if ($last['end'] != $max) {
                $emptySlots[] = $this->createEmptySlot(
                    $last['end_time'],
                    $maxTime
                );
            }
            foreach ($emptySlots as $emptySlot) {
                $daySlots[] = $emptySlot;
            }
            $this->sortSlots($daySlots);
        }
        $numOfSlots = ceil(max(($max - $min) * $slotsInHour, 0));
        // final sort
        return [
            'slots' => $slots,
            'numOfSlots' => $numOfSlots,
            'slotsInHour' => $slotsInHour,
            'min' => $min,
            'max' => $max,
        ];
    }

    /**
     * Sort slots by start
     *
     * @param array $slots slots to sort
     *
     * @return void
     */
    protected function sortSlots(&$slots)
    {
        usort(
            $slots,
            function ($a, $b) {
                return $a['start'] <=> $b['start'];
            }
        );
    }

    /**
     * Convert string to time as float
     *
     * @param string $time time
     *
     * @return float|int time
     */
    protected function convertTime($time)
    {
        [$hour, $min] = explode(':', $time);
        return $hour + ($min / 60);
    }

    /**
     * Create empty slot
     *
     * @param string $startTime start time
     * @param string $endTime   end time
     *
     * @return array
     */
    protected function createEmptySlot($startTime, $endTime)
    {
        return [
            'slot' => null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start' => $this->convertTime($startTime),
            'end' => $this->convertTime($endTime),
            'available' => false,
            'virtual' => true,
        ];
    }
}
