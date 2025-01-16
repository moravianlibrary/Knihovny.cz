<?php

namespace KnihovnyCz\Search\Solr;

/**
 * Filter
 *
 * @category VuFind
 * @package  Search
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class FilterQuery
{
    protected bool $neg = false;

    protected ?string $field = null;

    protected bool $parent = false;

    protected bool $child = false;

    protected ?string $tag = null;

    protected ?string $query;

    protected ?string $childQuery;

    /**
     * Get field
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Set field
     *
     * @param ?string $field field
     *
     * @return $this
     */
    public function setField(?string $field): FilterQuery
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Get query
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Set query
     *
     * @param string $query query
     *
     * @return $this
     */
    public function setQuery(string $query): FilterQuery
    {
        $this->query = $this->escapeValue($query);
        return $this;
    }

    /**
     * Set query
     *
     * @param string $query query
     *
     * @return $this
     */
    public function setRawQuery(string $query): FilterQuery
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Get negation
     *
     * @return bool
     */
    public function isNegation(): bool
    {
        return $this->neg;
    }

    /**
     * Set negation
     *
     * @param bool $neg negation
     *
     * @return $this
     */
    public function setNegation(bool $neg): FilterQuery
    {
        $this->neg = $neg;
        return $this;
    }

    /**
     * Enable parent query parser
     *
     * @param bool $enabled enabled
     *
     * @return $this
     */
    public function setParentQueryParser($enabled = true): FilterQuery
    {
        $this->parent = $enabled;
        return $this;
    }

    /**
     * Is enabled parent query parser
     *
     * @return bool
     */
    public function isParentQueryParser(): bool
    {
        return $this->parent;
    }

    /**
     * Enable children query parser
     *
     * @param bool $enabled enabled
     *
     * @return $this
     */
    public function setChildrenQueryParser($enabled = true): FilterQuery
    {
        $this->child = $enabled;
        return $this;
    }

    /**
     * Is enabled children query parser
     *
     * @return bool
     */
    public function isChildrenQueryParser(): bool
    {
        return $this->child;
    }

    /**
     * Get tag
     *
     * @return string|null tag
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * Set tag
     *
     * @param string|null $tag tag
     *
     * @return $this
     */
    public function setTag(?string $tag): FilterQuery
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * Get child query
     *
     * @return string|null
     */
    public function getChildQuery(): ?string
    {
        return $this->childQuery;
    }

    /**
     * Set child query
     *
     * @param string|null $childQuery child query
     *
     * @return $this
     */
    public function setChildQuery(?string $childQuery): FilterQuery
    {
        $this->childQuery = $childQuery;
        return $this;
    }

    /**
     * Get filter
     *
     * @return string
     */
    public function getFilter(): string
    {
        $filter = $this->query;
        if ($this->field != null) {
            $filter = $this->field . ':' . $this->query;
        }
        $hasLocalParams = ($this->tag != null || $this->parent || $this->child);
        if ($hasLocalParams) {
            $parentFilter = DeduplicationHelper::PARENT_FILTER;
            $localParams = '{!';
            if ($this->tag != null) {
                $localParams .= "tag=$this->tag ";
            }
            if ($this->parent) {
                $filter = addcslashes($filter, '"\'');
                if ($this->childQuery != null) {
                    $filter = '(' . $this->childQuery . ') AND ' . $filter;
                }
                $localParams .= "parent which='$parentFilter' v='$filter'";
                $filter = null;
            } elseif ($this->child) {
                $filter = addcslashes($filter, '"\'');
                $localParams .= "child of='$parentFilter' v='$filter'";
                $filter = null;
            }
            $localParams = rtrim($localParams) . '}';
            if ($filter == null) {
                $filter = $localParams;
            } else {
                $filter = $localParams . ' (' . $filter . ')';
            }
        }
        if ($this->neg) {
            $filter = '-(' . $filter . ')';
        }
        return $filter;
    }

    /**
     * Escape value for Solr
     *
     * @param string $value value
     *
     * @return string
     */
    protected function escapeValue(string $value): string
    {
        if (
            !(str_ends_with($value, '*')
                || preg_match('/\[[^\]]+\s+TO\s+[^\]]+\]/', $value))
        ) {
            $value = '"' . addcslashes($value, '"\\') . '"';
        }
        return $value;
    }
}
