<?php

/**
 * Class NullSessionManager
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
 * @package  KnihovnyCz\Session
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Session;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Session\AbstractManager;
use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\ValidatorChain;

/**
 * Class PerRequestSessionManager
 *
 * Dummy implementation of session manager to avoid use of session in AJAX
 * requests.
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Session
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class NullSessionManager extends AbstractManager
{
    /**
     * Validation chain to determine if session is valid
     *
     * @var EventManagerInterface
     */
    protected $validatorChain;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setStorage(new ArrayStorage());
    }

    /**
     * Does a session exist and is it currently active?
     *
     * @return bool
     */
    public function sessionExists()
    {
        return true;
    }

    /**
     * Start session
     *
     * @return void
     */
    public function start()
    {
    }

    /**
     * Destroy/end a session
     *
     * @return void
     */
    public function destroy()
    {
    }

    /**
     * Write session to save handler and close
     *
     * Once done, the Storage object will be marked as isImmutable.
     *
     * @return void
     */
    public function writeClose()
    {
        $storage  = $this->getStorage();
        if (! $storage->isImmutable()) {
            $storage->markImmutable();
        }
    }

    /**
     * Attempt to set the session name
     *
     * If the session has already been started, or if the name provided fails
     * validation, an exception will be raised.
     *
     * @param string $name name
     *
     * @return NullSessionManager
     */
    public function setName($name)
    {
        return $this;
    }

    /**
     * Get session name
     *
     * @return string|null
     */
    public function getName()
    {
        return null;
    }

    /**
     * Set session ID
     *
     * Can safely be called in the middle of a session.
     *
     * @param string $id id
     *
     * @return NullSessionManager
     */
    public function setId($id)
    {
        return $this;
    }

    /**
     * Get session ID
     *
     * @return string|null
     */
    public function getId()
    {
        return null;
    }

    /**
     * Regenerate id
     *
     * Regenerate the session ID, using session save handler's
     * native ID generation Can safely be called in the middle of a session.
     *
     * @return NullSessionManager
     */
    public function regenerateId()
    {
        return $this;
    }

    /**
     * Set the TTL (in seconds) for the session cookie expiry
     *
     * Can safely be called in the middle of a session.
     *
     * @param null|int $ttl time to live
     *
     * @return NullSessionManager
     */
    public function rememberMe($ttl = null)
    {
        return $this;
    }

    /**
     * Set a 0s TTL for the session cookie
     *
     * Can safely be called in the middle of a session.
     *
     * @return NullSessionManager
     */
    public function forgetMe()
    {
        return $this;
    }

    /**
     * Expire the session cookie
     *
     * Sends a session cookie with no value, and with an expiry in the past.
     *
     * @return void
     */
    public function expireSessionCookie()
    {
    }

    /**
     * Set the validator chain to use when validating a session
     *
     * In most cases, you should use an instance of {@link ValidatorChain}.
     *
     * @param EventManagerInterface $chain validator chain
     *
     * @return NullSessionManager
     */
    public function setValidatorChain(EventManagerInterface $chain)
    {
        return $this;
    }

    /**
     * Get the validator chain to use when validating a session
     *
     * By default, uses an instance of {@link ValidatorChain}.
     *
     * @return EventManagerInterface
     */
    public function getValidatorChain()
    {
        if (null === $this->validatorChain) {
            $this->setValidatorChain(new EventManager());
        }
        return $this->validatorChain;
    }

    /**
     * Is this session valid?
     *
     * Notifies the Validator Chain until either all validators have returned
     * true or one has failed.
     *
     * @return bool
     */
    public function isValid()
    {
        return true;
    }
}
