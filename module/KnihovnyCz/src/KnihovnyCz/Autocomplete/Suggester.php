<?php
/**
 * Autocomplete handler plugin manager
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Autocomplete
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:autosuggesters Wiki
 */
namespace KnihovnyCz\Autocomplete;

use Laminas\Stdlib\Parameters;
use VuFind\Autocomplete\PluginManager;
use VuFind\Config\PluginManager as ConfigManager;
use VuFind\Search\Options\PluginManager as OptionsManager;

/**
 * Autocomplete handler plugin manager
 *
 * @category Knihovny.cz
 * @package  Autocomplete
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:autosuggesters Wiki
 */
class Suggester implements \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Autocomplete plugin manager.
     *
     * @var PluginManager
     */
    protected $pluginManager = null;

    /**
     * Search options plugin manager.
     *
     * @var OptionsManager
     */
    protected $optionsManager = null;

    /**
     * Configuration manager.
     *
     * @var ConfigManager
     */
    protected $configManager = null;

    /**
     * Constructor
     *
     * @param PluginManager  $pm Autocomplete plugin manager
     * @param ConfigManager  $cm Config manager
     * @param OptionsManager $om Options manager
     */
    public function __construct(
        PluginManager $pm,
        ConfigManager $cm,
        OptionsManager $om
    ) {
        $this->pluginManager = $pm;
        $this->configManager = $cm;
        $this->optionsManager = $om;
    }

    /**
     * This returns an array of suggestions based on current request parameters.
     * This logic is present in the factory class so that it can be easily shared
     * by multiple AJAX handlers.
     *
     * @param Parameters $request    The user request
     * @param string     $typeParam  Request parameter containing search type
     * @param string     $queryParam Request parameter containing query string
     *
     * @return array
     */
    public function getSuggestions($request, $typeParam = 'type', $queryParam = 'q')
    {
        $type = $request->get($typeParam, '');
        $query = $request->get($queryParam, '');
        $types = $this->getTypes($type);
        $limit = (count($types) > 1) ? 6 : 10;
        $result = [
            [
                'label' => $this->translate('Autocomplete header'),
                'items' => [],
            ]
        ];
        foreach ($types as $type) {
            $items = [];
            $suggestions = $this->getSuggestionsByType(
                $request,
                $type,
                $query,
                $limit
            );
            if (empty($suggestions)) {
                continue;
            }
            foreach ($suggestions as $item) {
                if (is_scalar($item)) {
                    $items[] = [
                        'value' => $item,
                        'type' => $type,
                    ];
                } else {
                    $item['type'] = $type;
                    $items[] = $item;
                }
            }
            $result[] = [
                'label' => $this->translate('Search in ' . $type),
                'items' => $items,
            ];
        }
        return $result;
    }

    /**
     * Return search types to use for suggestions
     *
     * @param string $type Search type
     *
     * @return array search types for suggestions
     */
    protected function getTypes($type)
    {
        if ($type == 'AllFields') {
            return ['Title', 'Author', 'Subject'];
        } elseif ($type == 'AllLibraries') {
            return ['Name', 'Town'];
        } else {
            return [ $type ];
        }
    }

    /**
     * This returns an array of suggestions based on current request parameters.
     * This logic is present in the factory class so that it can be easily shared
     * by multiple AJAX handlers.
     *
     * @param Parameters $request The user request
     * @param string     $type    Search type
     * @param string     $query   Search query
     * @param int        $limit   Search query
     *
     * @return array
     */
    protected function getSuggestionsByType($request, $type, $query, $limit)
    {
        // Process incoming parameters:
        $searcher = $request->get('searcher', 'Solr');
        $hiddenFilters = $request->get('hiddenFilters', []);

        // If we're using a combined search box, we need to override the searcher
        // and type settings.
        if (substr($type, 0, 7) == 'VuFind:') {
            [, $tmp] = explode(':', $type, 2);
            [$searcher, $type] = explode('|', $tmp, 2);
        }

        // get Autocomplete_Type config
        $options = $this->optionsManager->get($searcher);
        $config = $this->configManager->get($options->getSearchIni());
        $types = isset($config->Autocomplete_Types) ?
            $config->Autocomplete_Types->toArray() : [];

        // Figure out which handler to use:
        if (!empty($type) && isset($types[$type])) {
            $module = $types[$type];
        } elseif (isset($config->Autocomplete->default_handler)) {
            $module = $config->Autocomplete->default_handler;
        } else {
            $module = false;
        }
        $handler = null;
        // Get suggestions:
        if ($module) {
            if (strpos($module, ':') === false) {
                $module .= ':'; // force colon to avoid warning in explode below
            }
            [$name, $params] = explode(':', $module, 2);
            $handler = $this->pluginManager->get($name);
            $handler->setConfig($params);
        } else {
            $handler = null;
        }

        if (is_object($handler) && is_callable([$handler, 'addFilters'])) {
            $handler->addFilters($hiddenFilters);
        }

        // if the handler needs the complete request, pass it on
        if (is_object($handler) && is_callable([$handler, 'setRequest'])) {
            $handler->setRequest($request);
        }

        $result = is_object($handler) && is_callable([$handler, 'getSuggestions'])
            ? array_values($handler->getSuggestions($query)) : [];
        return array_splice($result, 0, $limit);
    }
}
