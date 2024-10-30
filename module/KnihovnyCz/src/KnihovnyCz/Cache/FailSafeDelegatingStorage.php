<?php

namespace KnihovnyCz\Cache;

use Laminas\Cache\Storage\Adapter;
use Laminas\Cache\Storage\Capabilities;
use Laminas\Cache\Storage\StorageInterface;
use Traversable;

/**
 * Class FailSafeDelegatingStorage - when deserialization fails, remove the item
 * from the cache
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Cache
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class FailSafeDelegatingStorage implements StorageInterface
{
    /**
     * Delegate
     *
     * @var \Laminas\Cache\Storage\StorageInterface
     */
    protected StorageInterface $delegate;

    /**
     * Create fail safe delegating storage.
     *
     * @param \Laminas\Cache\Storage\StorageInterface $delegate delegate
     */
    public function __construct(StorageInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * Set options.
     *
     * @param array|Traversable|Adapter\AdapterOptions $options options
     *
     * @return StorageInterface Fluent interface
     */
    public function setOptions($options)
    {
        return $this->delegate->setOptions($options);
    }

    /**
     * Get options
     *
     * @return Adapter\AdapterOptions
     */
    public function getOptions()
    {
        return $this->delegate->getOptions();
    }

    /**
     * Get an item.
     *
     * @param string $key      key
     * @param bool   $success  success
     * @param mixed  $casToken CAS token
     *
     * @return mixed Data on success, null on failure
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function getItem($key, &$success = null, &$casToken = null)
    {
        try {
            return $this->delegate->getItem($key, $success, $casToken);
        } catch (\Laminas\Serializer\Exception\RuntimeException $ex) {
            $this->removeItem($key);
        }
        return null;
    }

    /**
     * Get multiple items.
     *
     * @param array $keys keys
     *
     * @return array Associative array of keys and values
     *
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function getItems(array $keys)
    {
        return $this->delegate->getItems($keys);
    }

    /**
     * Test if an item exists.
     *
     * @param string $key key
     *
     * @return bool
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function hasItem($key)
    {
        return $this->delegate->hasItem($key);
    }

    /**
     * Test multiple items.
     *
     * @param array $keys keys
     *
     * @return array Array of found keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function hasItems(array $keys)
    {
        return $this->delegate->hasItems($keys);
    }

    /**
     * Get metadata of an item.
     *
     * @param string $key key
     *
     * @return array|bool Metadata on success, false on failure
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function getMetadata($key)
    {
        return $this->delegate->getMetadata($key);
    }

    /**
     * Get multiple metadata
     *
     * @param array $keys keys
     *
     * @return array Associative array of keys and metadata
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function getMetadatas(array $keys)
    {
        return $this->delegate->getMetadatas($keys);
    }

    /**
     * Store an item.
     *
     * @param string $key   key
     * @param mixed  $value value
     *
     * @return bool
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function setItem($key, $value)
    {
        return $this->delegate->setItem($key, $value);
    }

    /**
     * Store multiple items.
     *
     * @param array $keyValuePairs Associative array of keys and values
     *
     * @return array Array of not stored keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function setItems(array $keyValuePairs)
    {
        return $this->delegate->setItems($keyValuePairs);
    }

    /**
     * Add an item.
     *
     * @param string $key   key
     * @param mixed  $value value
     *
     * @return bool
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function addItem($key, $value)
    {
        return $this->delegate->addItem($key, $value);
    }

    /**
     * Add multiple items.
     *
     * @param array $keyValuePairs Associative array of keys and values
     *
     * @return array Array of not stored keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function addItems(array $keyValuePairs)
    {
        return $this->delegate->addItems($keyValuePairs);
    }

    /**
     * Replace an existing item.
     *
     * @param string $key   key
     * @param mixed  $value value
     *
     * @return bool
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function replaceItem($key, $value)
    {
        return $this->delegate->replaceItem($key, $value);
    }

    /**
     * Replace multiple existing items.
     *
     * @param array $keyValuePairs Associative array of keys and values
     *
     * @return array Array of not stored keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function replaceItems(array $keyValuePairs)
    {
        return $this->delegate->replaceItems($keyValuePairs);
    }

    /**
     * Set an item only if token matches
     *
     * It uses the token received from getItem() to check if the item has
     * changed before overwriting it.
     *
     * @param mixed  $token token
     * @param string $key   key
     * @param mixed  $value value
     *
     * @return bool
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function checkAndSetItem($token, $key, $value)
    {
        return $this->delegate->checkAndSetItem($token, $key, $value);
    }

    /**
     * Reset lifetime of an item
     *
     * @param string $key key
     *
     * @return bool
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function touchItem($key)
    {
        return $this->delegate->touchItem($key);
    }

    /**
     * Reset lifetime of multiple items.
     *
     * @param array $keys keys
     *
     * @return array Array of not updated keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function touchItems(array $keys)
    {
        return $this->delegate->touchItems($keys);
    }

    /**
     * Remove an item.
     *
     * @param string $key key
     *
     * @return bool
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function removeItem($key)
    {
        return $this->delegate->removeItem($key);
    }

    /**
     * Remove multiple items.
     *
     * @param array $keys keys
     *
     * @return array Array of not removed keys
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function removeItems(array $keys)
    {
        return $this->delegate->removeItems($keys);
    }

    /**
     * Increment an item.
     *
     * @param string $key   key
     * @param int    $value value
     *
     * @return int|bool The new value on success, false on failure
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function incrementItem($key, $value)
    {
        return $this->delegate->incrementItem($key, $value);
    }

    /**
     * Increment multiple items.
     *
     * @param array $keyValuePairs Associative array of keys and values
     *
     * @return array Associative array of keys and new values
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function incrementItems(array $keyValuePairs)
    {
        return $this->delegate->incrementItems($keyValuePairs);
    }

    /**
     * Decrement an item.
     *
     * @param string $key   key
     * @param int    $value value
     *
     * @return int|bool The new value on success, false on failure
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function decrementItem($key, $value)
    {
        return $this->delegate->decrementItem($key, $value);
    }

    /**
     * Decrement multiple items.
     *
     * @param array $keyValuePairs Associative array of keys and values
     *
     * @return array Associative array of keys and new values
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function decrementItems(array $keyValuePairs)
    {
        return $this->delegate->decrementItems($keyValuePairs);
    }

    /**
     * Capabilities of this storage
     *
     * @return Capabilities
     */
    public function getCapabilities()
    {
        return $this->delegate->getCapabilities();
    }
}
