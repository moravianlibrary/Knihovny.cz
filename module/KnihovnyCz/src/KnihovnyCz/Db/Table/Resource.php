<?php

namespace KnihovnyCz\Db\Table;

use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

/**
 * Class Resource
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Resource extends \VuFind\Db\Table\Resource
{
    protected const SORT_FIELDS = [
        'author' => 'author_sort',
    ];

    /**
     * Return all resources.
     *
     * @return \Laminas\Db\ResultSet\AbstractResultSet
     */
    public function findAll()
    {
        return $this->select();
    }

    /**
     * Get a set of records from the requested favorite list.
     *
     * @param string $user   ID of user owning favorite list
     * @param string $list   ID of list to retrieve (null for all favorites)
     * @param array  $tags   Tags to use for limiting results
     * @param string $sort   Resource table field to use for sorting (null for
     * no particular sort).
     * @param int    $offset Offset for results
     * @param int    $limit  Limit for results (null for none)
     *
     * @return \Laminas\Db\ResultSet\AbstractResultSet
     */
    public function getFavorites(
        $user,
        $list = null,
        $tags = [],
        $sort = null,
        $offset = 0,
        $limit = null
    ) {
        // Set up base query:
        $obj = & $this;
        return $this->select(
            function ($s) use ($user, $list, $tags, $sort, $offset, $limit, $obj) {
                $columns = [
                    new Expression(
                        'DISTINCT(?)',
                        ['resource.id'],
                        [Expression::TYPE_IDENTIFIER]
                    ),
                    Select::SQL_STAR,
                    new Expression('saved'),
                ];
                $s->columns($columns);
                $s->join(
                    ['ur' => 'user_resource'],
                    'resource.id = ur.resource_id',
                    []
                );
                $s->where->equalTo('ur.user_id', $user);

                // Adjust for list if necessary:
                if (null !== $list) {
                    $s->where->equalTo('ur.list_id', $list);
                }

                if ($offset > 0) {
                    $s->offset($offset);
                }
                if (null !== $limit) {
                    $s->limit($limit);
                }

                // Adjust for tags if necessary:
                if (!empty($tags)) {
                    $linkingTable = $obj->getDbTable('ResourceTags');
                    foreach ($tags as $tag) {
                        $matches = $linkingTable
                            ->getResourcesForTag($tag, $user, $list)->toArray();
                        $getId = function ($i) {
                            return $i['resource_id'];
                        };
                        $s->where->in('resource.id', array_map($getId, $matches));
                    }
                }

                // Apply sorting, if necessary:
                if (!empty($sort)) {
                    $alias = 'resource';
                    if ($sort == 'saved' || $sort == 'saved DESC') {
                        $alias = 'ur';
                    }
                    Resource::applySort($s, $sort, $alias, $columns);
                }
            }
        );
    }

    /**
     * Apply a sort parameter to a query on the resource table.
     *
     * @param \Laminas\Db\Sql\Select $query   Query to modify
     * @param string                 $sort    Field to use for sorting (may include 'desc' qualifier)
     * @param string                 $alias   Alias to the resource table (defaults to 'resource')
     * @param array                  $columns Existing list of columns to select
     *
     * @return void
     */
    public static function applySort($query, $sort, $alias = 'resource', $columns = [])
    {
        // Apply sorting, if necessary:
        $legalSorts = [
            'title', 'title desc', 'author', 'author desc', 'year',
            'year desc', 'saved desc', 'saved asc',
        ];
        if (!empty($sort) && in_array(strtolower($sort), $legalSorts)) {
            // Strip off 'desc' to obtain the raw field name -- we'll need it
            // to sort null values to the bottom:
            $parts = explode(' ', $sort);
            $rawField = trim($parts[0]);
            if (($sortField = self::SORT_FIELDS[$rawField] ?? null) != null) {
                $sort = $rawField = $sortField;
                if (isset($parts[1])) {
                    $sort .= ' ' . $parts[1];
                }
            }

            // Start building the list of sort fields:
            $order = [];

            // The title field can't be null, so don't bother with the extra
            // isnull() sort in that case.
            if (strtolower($rawField) != 'title') {
                $expression = new Expression(
                    'isnull(?)',
                    [$alias . '.' . $rawField],
                    [Expression::TYPE_IDENTIFIER]
                );
                $query->columns(array_merge($columns, [$expression]));
                $order[] = $expression;
            }

            // Apply the user-specified sort:
            $order[] = $alias . '.' . $sort;

            // Inject the sort preferences into the query object:
            $query->order($order);
        }
    }
}
