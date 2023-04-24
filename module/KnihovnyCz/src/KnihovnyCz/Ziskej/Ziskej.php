<?php

/**
 * Class ZiskejApiFactory
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
 * @package  KnihovnyCz\Ziskej
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Ziskej;

use Laminas\Config\Config;
use VuFind\Cookie\CookieManager;

/**
 * KnihovnyCz Ziskej Class
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Ziskej
 * @author   Robert Šípek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
abstract class Ziskej
{
    public const MODE_DISABLED = 'disabled';

    public const MODE_PRODUCTION = 'prod';

    /**
     * Cookie name
     */
    protected const COOKIE_NAME = 'ziskej_mode';

    /**
     * Default mode name
     */
    protected const CONFIG_DEFAULT_MODE_NAME = 'default_mode';

    /**
     * Main configuration
     *
     * @var Config
     */
    private Config $config;

    /**
     * Ziskej configuration
     *
     * @var Config
     */
    private Config $configZiskej;

    /**
     * Cookie manager
     *
     * @var CookieManager
     */
    private CookieManager $cookieManager;

    /**
     * Default execution mode
     *
     * @var string
     */
    private string $defaultMode;

    /**
     * Constructor
     *
     * @param Config        $config        Main configuration
     * @param CookieManager $cookieManager Cookie manager
     */
    public function __construct(
        Config $config,
        CookieManager $cookieManager
    ) {
        $this->config = $config;
        $this->configZiskej = $this->config->get('Ziskej');
        $this->cookieManager = $cookieManager;

        $this->defaultMode = $this->configZiskej[self::CONFIG_DEFAULT_MODE_NAME]
            ?: self::MODE_DISABLED;
    }

    /**
     * Get if ziskej is enabled on cpk
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getCurrentMode() !== self::MODE_DISABLED;
    }

    /**
     * Get list of Ziskej API urls
     *
     * @return string[]
     */
    public function getUrls(): array
    {
        return !empty($this->configZiskej['mode_urls'])
            ? $this->configZiskej['mode_urls']->toArray()
            : [];
    }

    /**
     * Get list of Ziskej API modes
     *
     * @return string[]
     */
    public function getModes(): array
    {
        return array_keys($this->getUrls());
    }

    /**
     * Check if mode exists
     *
     * @param string $mode Execution mode
     *
     * @return bool
     */
    public function isMode(string $mode): bool
    {
        return in_array($mode, $this->getModes());
    }

    /**
     * Get current mode
     *
     * @return string
     */
    public function getCurrentMode(): string
    {
        return !empty($this->cookieManager->get(self::COOKIE_NAME))
            ? $this->cookieManager->get(self::COOKIE_NAME)
            : $this->defaultMode;
    }

    /**
     * Set mode to cookie
     *
     * @param string $mode Execution mode
     *
     * @return void
     */
    public function setMode(string $mode): void
    {
        $cookieMode = $this->isMode($mode) ? $mode : self::MODE_DISABLED;
        \setcookie(self::COOKIE_NAME, $cookieMode, 0, '/');
    }

    /**
     * Get current base url
     *
     * @return string
     */
    public function getCurrentUrl(): string
    {
        return $this->getUrls()[$this->getCurrentMode()];
    }

    /**
     * Get location of private key file
     *
     * @return string
     * @throws \Exception
     */
    public function getPrivateKeyFileLocation(): string
    {
        $keyFile = $this->config->get('Certs')['ziskej'];

        if (!$keyFile || !is_readable($keyFile)) {
            throw new \Exception('Certificate file to generate token not found');
        }

        return $keyFile;
    }

    /**
     * Get techlib url
     *
     * @return string|null
     */
    public function getZiskejTechlibUrl(): ?string
    {
        return $this->configZiskej['techlib_url'];
    }

    /**
     * Get current techlib front url
     *
     * @return string|null
     */
    public function getCurrentZiskejTechlibFrontUrl(): ?string
    {
        $modeUrls = $this->config->get('ZiskejTechlibFrontUrl')->toArray();

        return $modeUrls[$this->getCurrentMode()] ?? null;
    }
}
