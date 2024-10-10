<?php

declare(strict_types=1);

namespace KnihovnyCz\Cache;

use VuFind\Cache\Manager as BaseManager;

/**
 * Cache Manager
 *
 * @category VuFind
 * @package  KnihovnyCz\Config
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Manager extends BaseManager
{
    /**
     * Wrap cache storage in fail-safe delegating storage
     *
     * @param string $name      name
     * @param string $namespace namespace
     *
     * @return \VuFind\Cache\StorageInterface
     */
    public function getCache($name, $namespace = null)
    {
        $cache = parent::getCache($name, $namespace);
        return new FailSafeDelegatingStorage($cache);
    }
}
