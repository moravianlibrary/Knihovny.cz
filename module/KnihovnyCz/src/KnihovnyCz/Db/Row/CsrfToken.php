<?php

namespace KnihovnyCz\Db\Row;

use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\DbTableAwareInterface;

/**
 * Class CsrfToken
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Row
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class CsrfToken extends RowGateway implements DbTableAwareInterface
{
    use \VuFind\Db\Table\DbTableAwareTrait;

    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     *
     * @return void
     */
    public function __construct(\Laminas\Db\Adapter\Adapter $adapter)
    {
        parent::__construct('id', 'csrf_token', $adapter);
    }
}
