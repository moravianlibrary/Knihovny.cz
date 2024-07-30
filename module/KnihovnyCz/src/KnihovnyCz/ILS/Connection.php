<?php

namespace KnihovnyCz\ILS;

use VuFind\Exception\ILS as ILSException;
use VuFind\ILS\Connection as ConnectionBase;

/**
 * Catalog Connection Class
 *
 * This wrapper works with a driver class to pass information from the ILS to
 * VuFind.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class Connection extends ConnectionBase
{
    /**
     * Methods to check for each library card
     */
    public const CHECKED_METHODS = [
        'getMyTransactionHistory',
        'getMyShortLoans',
        'ILLRequests',
    ];

    /**
     * Check Function
     *
     * This is responsible for checking the driver configuration to determine
     * if the system supports a particular function.
     *
     * @param string $method The name of the function to check.
     * @param array  $params (optional) An array of function-specific parameters
     *
     * @return mixed On success, an associative array with specific function keys
     * and values; on failure, false.
     */
    public function checkFunction($method, $params = [])
    {
        foreach ($this->getCapabilityParams($method, $params) as $param) {
            if ($result = parent::checkFunction($method, $param)) {
                return $result;
            }
        }
        return false;
    }

    /**
     * Check driver capability -- return true if the driver supports the specified
     * method; false otherwise.
     *
     * @param string $method Method to check
     * @param array  $params Array of passed parameters (optional)
     * @param bool   $throw  Whether to throw exceptions instead of returning false
     *
     * @return bool  if driver capability is supported
     * @throws ILSException
     */
    public function checkCapability($method, $params = [], $throw = false)
    {
        foreach ($this->getCapabilityParams($method, $params) as $param) {
            if (parent::checkCapability($method, $param, $throw)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get capability parameters to check for each connected library card
     *
     * @param string $method Method to check
     * @param array  $params Array of passed parameters (optional)
     *
     * @return array parameters to check
     */
    protected function getCapabilityParams($method, $params)
    {
        if (
            !(isset($params['user']) && in_array(
                $method,
                self::CHECKED_METHODS
            ))
        ) {
            return [ $params ];
        }
        $user = $params['user'] ?: null;
        $newParams = [];
        foreach ($user?->getLibraryCards() ?? [] as $card) {
            $newParams[] = [
                'patron' => [
                    'cat_username' => $card['cat_username'],
                ],
            ];
        }
        return $newParams;
    }

    /**
     * Check ShortLoans
     *
     * A support method for checkFunction(). This is responsible for checking
     * the driver configuration to determine if the system supports ShortLoans.
     *
     * @param array $functionConfig The Hold configuration values
     * @param array $params         An array of function-specific params (or null)
     *
     * @return mixed On success, an associative array with specific function keys
     * and values either for placing holds via a form or a URL; on failure, false.
     */
    protected function checkMethodgetMyShortLoans($functionConfig, $params)
    {
        if (parent::checkCapability('getMyShortLoans', [$params ?: []])) {
            return true;
        }
        return false;
    }

    /**
     * Check PaymentLink
     *
     * A support method for checkFunction(). This is responsible for checking
     * the driver configuration to determine if the system supports PaymentLink.
     *
     * @param array $functionConfig The Hold configuration values
     * @param array $params         An array of function-specific params (or null)
     *
     * @return mixed On success, an associative array with specific function keys
     * and values either for placing holds via a form or a URL; on failure, false.
     */
    protected function checkMethodgetMyPaymentLink($functionConfig, $params)
    {
        if (parent::checkCapability('getMyPaymentLink', [$params ?: []])) {
            return true;
        }
        return false;
    }

    /**
     * Check prolonging of registration
     *
     * A support method for checkFunction(). This is responsible for checking
     * the driver configuration to determine if the system supports link
     * for prolonging of registration.
     *
     * @param array $functionConfig The Hold configuration values
     * @param array $params         An array of function-specific params (or null)
     *
     * @return mixed On success, an associative array with specific function keys
     * and values either for placing holds via a form or a URL; on failure, false.
     */
    protected function checkMethodgetMyProlongRegistrationLink(
        $functionConfig,
        $params
    ) {
        if (
            parent::checkCapability(
                'getMyProlongRegistrationLink',
                [$params ?: []]
            )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check getMyILLRequests
     *
     * A support method for checkFunction(). This is responsible for checking
     * the driver configuration to determine if the system supports getMyILLRequests.
     *
     * @param array $functionConfig The ILL request configuration values
     * @param array $params         An array of function-specific params (or null)
     *
     * @return bool if driver capability is supported
     */
    protected function checkMethodILLRequests($functionConfig, $params)
    {
        if (parent::checkCapability('getMyILLRequests', [$params ?: []])) {
            return true;
        }
        return false;
    }

    /**
     * Check getFormsForILLRequest
     *
     * A support method for checkFunction(). This is responsible for checking
     * the driver configuration to determine if the system supports
     * getFormsForILLRequest.
     *
     * @param array $functionConfig The ILL request configuration values
     * @param array $params         An array of function-specific params (or null)
     *
     * @return bool if driver capability is supported
     */
    protected function checkMethodgetBlankIllRequestTypes($functionConfig, $params)
    {
        if (parent::checkCapability('getBlankIllRequestTypes', [$params ?: []])) {
            return true;
        }
        return false;
    }

    /**
     * Check changeEmail
     *
     * A support method for checkFunction(). This is responsible for checking
     * the driver configuration to determine if the system supports
     * changeEmail.
     *
     * @param array $functionConfig The ILL request configuration values
     * @param array $params         An array of function-specific params (or null)
     *
     * @return bool if driver capability is supported
     */
    protected function checkMethodchangeEmail($functionConfig, $params)
    {
        if (parent::checkCapability('changeEmail', [$params ?: []])) {
            return true;
        }
        return false;
    }

    /**
     * Check nickname
     *
     * A support method for checkFunction(). This is responsible for checking
     * the driver configuration to determine if the system supports
     * changeNickname.
     *
     * @param array $functionConfig The ILL request configuration values
     * @param array $params         An array of function-specific params (or null)
     *
     * @return bool if driver capability is supported
     */
    protected function checkMethodchangeNickname($functionConfig, $params)
    {
        if (parent::checkCapability('changeNickname', [$params ?: []])) {
            return true;
        }
        return false;
    }

    /**
     * Check getMyBlocks
     *
     * A support method for checkFunction(). This is responsible for checking
     * the driver configuration to determine if the system supports
     * changeNickname.
     *
     * @param array $functionConfig The ILL request configuration values
     * @param array $params         An array of function-specific params (or null)
     *
     * @return bool if driver capability is supported
     */
    protected function checkMethodgetMyBlocks($functionConfig, $params)
    {
        if (parent::checkCapability('getMyBlocks', [$params ?: []])) {
            return true;
        }
        return false;
    }
}
