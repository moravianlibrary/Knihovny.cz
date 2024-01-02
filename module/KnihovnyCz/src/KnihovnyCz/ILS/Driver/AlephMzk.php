<?php

declare(strict_types=1);

namespace KnihovnyCz\ILS\Driver;

use KnihovnyCz\ILS\Driver\Aleph as AlephBase;
use VuFind\Date\DateException;
use VuFind\Exception\ILS as ILSException;

/**
 * Class AlephMzk
 *
 * @category VuFind
 * @package  KnihovnyCz\ILS\Driver
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AlephMzk extends AlephBase
{
    protected $userCgiUrl = null;

    /**
     * Initialize the driver.
     *
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     *
     * @throws ILSException
     * @return void
     */
    public function init()
    {
        parent::init();
        if (isset($this->config['Catalog']['userCgiUrl'])) {
            $this->userCgiUrl = $this->config['Catalog']['userCgiUrl'];
        }
    }

    /**
     * Helper method to determine whether or not a certain method can be
     * called on this driver.  Required method for any smart drivers.
     *
     * @param string $method The name of the called method.
     * @param array  $params Array of passed parameters
     *
     * @return bool True if the method can be called with the given parameters,
     * false otherwise.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsMethod($method, $params)
    {
        if (in_array($method, ['changePassword', 'changeNickname', 'changeEmail'])) {
            return $this->userCgiUrl != null;
        }
        return parent::supportsMethod($method, $params);
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $user The patron array
     *
     * @throws ILSException
     * @return array      Array of the patron's profile data on success.
     */
    public function getMyProfile($user): array
    {
        $profile = parent::getMyProfile($user);
        if (isset($profile['barcode'])) {
            $profile['bookshelf'] = substr($profile['barcode'], -2, 2);
        }
        return $profile;
    }

    /**
     * Change patron's password
     *
     * @param array $input array with patron, old and new password
     *
     * @throws ILSException
     * @return array
     */
    public function changePassword($input): array
    {
        $patron = $input['patron'];
        $params = [
            'op'      => 'change_password',
            'old_pwd' => $input['oldPassword'],
            'new_pwd' => $input['newPassword'],
        ];
        return $this->changeUserRequest($patron, $params);
    }

    /**
     * Change patron's email address
     *
     * @param array $input input array with patron and new email address
     *
     * @throws ILSException
     * @return array
     */
    public function changeEmail($input)
    {
        $patron = $input['patron'];
        $params = [
            'op'    => 'change_email',
            'email' => $input['email'],
        ];
        return $this->changeUserRequest($patron, $params);
    }

    /**
     * Get patron's nickname
     *
     * @param array $patron patron
     *
     * @throws ILSException
     * @return string
     */
    public function getNickname($patron)
    {
        $params = [
            'op'           => 'get_nickname',
        ];
        $xml = $this->changeUserRequest($patron, $params, true);
        if ($xml->error) {
            if ($xml->error == 'no nick') {
                return null;
            } else {
                throw new ILSException((string)$xml->error);
            }
        }
        return $xml->nick;
    }

    /**
     * Change patron's nickname
     *
     * @param array $input input array with patron and nickname
     *
     * @throws ILSException
     * @return array
     */
    public function changeNickname($input)
    {
        $patron = $input['patron'];
        $params = [
            'op'           => 'change_nickname',
            'new_nickname' => $input['nickname'],
        ];
        return $this->changeUserRequest($patron, $params);
    }

    /**
     * Change user request
     *
     * @param array   $patron       patron
     * @param array   $params       parameters
     * @param boolean $returnResult return XML with results
     *
     * @throws \Exception
     * @return array|\SimpleXMLElement
     */
    public function changeUserRequest($patron, $params, $returnResult = false): mixed
    {
        if ($this->userCgiUrl == null) {
            throw new \Exception('Not supported, missing [Catalog][userCgiUrl] section in config');
        }
        $params['id']            = $patron['id'];
        $params['user_name']     = $this->wwwuser;
        $params['user_password'] = $this->wwwpasswd;
        $response = $this->httpService->get($this->userCgiUrl, $params);
        if (!$response->isSuccess()) {
            throw new ILSException('HTTP error');
        }
        $answer = $response->getBody();
        $xml = simplexml_load_string($answer);
        if ($returnResult) {
            return $xml;
        }
        if ($xml->error) {
            return [ 'success' => false, 'status' => $xml->error ];
        } else {
            return [ 'success' => true ];
        }
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $user The patron array from patronLogin
     *
     * @return array      Array of the patron's holds on success.
     * @throws ILSException
     * @throws DateException
     */
    public function getMyHolds($user)
    {
        $holds = parent::getMyHolds($user);
        $ready = [];
        $waiting = [];
        foreach ($holds as $hold) {
            if ($hold['z37_status'] == 'S') {
                $ready[] = $hold;
            } else {
                $waiting[] = $hold;
            }
        }
        return array_merge($ready, $waiting);
    }
}
