<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Row;

use VuFind\Db\Row\Resource as Base;

/**
 * Class Resource
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Table
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 * @see      \VuFind\Db\Table\GatewayFactory::getRowPrototype()
 *
 * @property ?string $author_sort
 */
class Resource extends Base
{
    /**
     * Use a record driver to assign metadata to the current row.  Return the
     * current object to allow fluent interface.
     *
     * @param \VuFind\RecordDriver\AbstractBase $driver    The record driver
     * @param \VuFind\Date\Converter            $converter Date converter
     *
     * @return \VuFind\Db\Row\Resource
     */
    public function assignMetadata($driver, \VuFind\Date\Converter $converter)
    {
        $result = parent::assignMetadata($driver, $converter);

        // Try to find an author for sorting; if not available, just leave
        // the default null:
        $authorSort = $driver->tryMethod('getPrimaryAuthorForSorting');
        if ($authorSort != null) {
            $authorSort = mb_substr($authorSort, 0, 255, 'UTF-8');
            $this->author_sort = $authorSort;
        }

        return $result;
    }
}
