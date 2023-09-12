<?php

/**
 * Knihovny.cz User settings service
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2022.
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
 * @category KnihovnyCz
 * @package  Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */

namespace KnihovnyCz\Service;

use KnihovnyCz\Db\Table\UserSettings;
use Laminas\Session\Container;
use VuFind\Config\PluginManager as Config;
use VuFind\Search\Memory;

/**
 * Knihovny.cz User settings service
 *
 * @category KnihovnyCz
 * @package  Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
class UserSettingsService
{
    /**
     * Config
     *
     * @var Config
     */
    protected $config;

    /**
     * Session container
     *
     * @var Container
     */
    protected $session;

    /**
     * Memory
     *
     * @var Memory
     */
    protected $memory;

    /**
     * Memory
     *
     * @var UserSettings
     */
    protected $userSettings;

    /**
     * Constructor.
     *
     * @param Container    $session      User session
     * @param Config       $config       Configuration manager
     * @param Memory       $memory       Memory
     * @param UserSettings $userSettings User settings
     *
     * @return void
     */
    public function __construct(
        Container $session,
        Config $config,
        Memory $memory,
        UserSettings $userSettings
    ) {
        $this->session = $session;
        $this->config = $config;
        $this->memory = $memory;
        $this->userSettings = $userSettings;
    }

    /**
     * Updates the user information in the session.
     *
     * @return void
     */
    public function restore()
    {
        $setting = $this->getUserSettings();
        $available = $this->getAvailableSettings();
        $availablePages = $available['recordsPerPage']['values'];
        if (
            $setting->records_per_page != null
            && in_array($setting->records_per_page, $availablePages)
        ) {
            foreach (['Solr', 'EDS', 'Search2'] as $searchClassId) {
                $this->memory->rememberLastSettings(
                    $searchClassId,
                    ['limit' =>
                             $setting->records_per_page]
                );
            }
        }
        $availableCitations = array_keys($available['citationStyle']['values']);
        if (
            $setting->citation_style != null
            && in_array($setting->citation_style, $availableCitations)
        ) {
            $this->session->citationStyle = $setting->citation_style;
        }
    }

    /**
     * Save user settings
     *
     * @param array $preferences preferences to save
     *
     * @return boolean
     */
    public function save($preferences)
    {
        $settings = $this->getAvailableSettings();
        $availablePages = $settings['recordsPerPage']['values'];
        $availableCitations = array_keys($settings['citationStyle']['values']);
        if (
            !in_array($preferences['recordsPerPage'], $availablePages)
            || !in_array($preferences['citationStyle'], $availableCitations)
        ) {
            return false;
        }
        $setting = $this->getUserSettings();
        $setting->records_per_page = $preferences['recordsPerPage'];
        $setting->citation_style = $preferences['citationStyle'];
        $setting->save();
        $this->restore();
        return true;
    }

    /**
     * Get available settings
     *
     * @return array
     */
    public function getAvailableSettings()
    {
        $setting = $this->getUserSettings();
        $settings = [];
        // citations
        $citation = $this->config->get('citation');
        $settings['citationStyle'] = [
            'selected' => $setting->citation_style
                ?? $citation->Citation->default_citation_style,
            'values' => $citation->Citation->citation_styles->toArray(),
        ];
        // records on page
        $searches = $this->config->get('searches');
        $limits = [];
        if (isset($searches->General->limit_options)) {
            $limits = explode(',', $searches->General->limit_options);
        }
        $settings['recordsPerPage'] = [
            'selected' => $setting->records_per_page
                ?? $this->memory->retrieveLastSetting('Solr', 'limit')
                ?? $citation->Citation->default_citation_style,
            'values' => $limits,
        ];
        return $settings;
    }

    /**
     * Get or create user settings for logged in user
     *
     * @return \KnihovnyCz\Db\Table\UserSettings
     *
     * @throws \Exception
     */
    public function getUserSettings()
    {
        if (!isset($this->session->userId)) {
            throw new \Exception('User not logged in');
        }
        return $this->userSettings->getOrCreateByUserId(
            $this->session->userId
        );
    }
}
