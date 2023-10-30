<?php

namespace KnihovnyCz\Db\Row;

use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\DbTableAwareInterface;

/**
 * Class InstConfigs
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Row
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InstConfigs extends RowGateway implements DbTableAwareInterface
{
    use \VuFind\Db\Table\DbTableAwareTrait;

    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('id', 'inst_configs', $adapter);
    }
}
