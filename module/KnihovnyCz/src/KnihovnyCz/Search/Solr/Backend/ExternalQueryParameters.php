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

    protected $deduplication = null;

    protected ?string $childFilter = null;

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
     * Get deduplication type
     *
     * @return string
     */
    public function getDeduplication(): ?string
    {
        return $this->deduplication;
    }

    /**
     * Set deduplication type
     *
     * @param string|null $deduplication deduplication type
     *
     * @return void
     */
    public function setDeduplication(string|null $deduplication): void
    {
        $this->deduplication = $deduplication;
    }

    /**
     * Get child filter
     *
     * @return string|null
     */
    public function getChildFilter(): ?string
    {
        return $this->childFilter;
    }

    /**
     * Set child filter
     *
     * @param string|null $childFilter child filter
     *
     * @return void
     */
    public function setChildFilter(?string $childFilter): void
    {
        $this->childFilter = $childFilter;
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
