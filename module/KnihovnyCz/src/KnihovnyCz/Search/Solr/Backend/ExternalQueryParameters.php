<?php

namespace KnihovnyCz\Search\Solr\Backend;

/**
 * External search query parameters
 *
 * @category KnihovnyCz
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class ExternalQueryParameters
{
    protected const PREFIX = 'query';

    protected $index = 0;

    protected $parameters = [];

    protected $switchToParentQuery = false;

    /**
     * Add search query to parameters and return parameter name for it
     *
     * @param string $search search
     *
     * @return string parameter name
     */
    public function add($search)
    {
        $key = self::PREFIX . $this->index++;
        $this->parameters[$key] = $search;
        return $key;
    }

    /**
     * Set switch to parent query
     *
     * @param bool $switchToParentQuery switch to parent query
     *
     * @return void
     */
    public function setSwitchToParentQuery(bool $switchToParentQuery)
    {
        $this->switchToParentQuery = $switchToParentQuery;
    }

    /**
     * Is set to switch parent query
     *
     * @return bool
     */
    public function isSwitchToParentQuery(): bool
    {
        return $this->switchToParentQuery;
    }

    /**
     * Return parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Reset this object for new query
     *
     * @return void
     */
    public function reset()
    {
        $this->index = 0;
        $this->parameters = [];
        $this->switchToParentQuery = false;
    }
}
