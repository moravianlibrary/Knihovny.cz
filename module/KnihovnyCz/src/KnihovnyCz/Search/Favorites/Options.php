<?php

namespace KnihovnyCz\Search\Favorites;

/**
 * Search Favorites Options
 *
 * @category VuFind
 * @package  Search_Favorites
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Options extends \VuFind\Search\Favorites\Options
{
    /**
     * Constructor
     *
     * @param \VuFind\Config\PluginManager $configLoader Config loader
     */
    public function __construct(\VuFind\Config\PluginManager $configLoader)
    {
        parent::__construct($configLoader);
        $this->sortOptions['saved DESC'] = 'sort_created';
        $this->defaultSort = 'saved DESC';
    }
}
