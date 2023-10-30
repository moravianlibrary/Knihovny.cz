<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Row;

use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\DbTableAwareInterface;

/**
 * Class UserSettings
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Db\Row
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 *
 * @property int     $id
 * @property int     $user_id
 * @property string  $saved_institutions
 */
class UserSettings extends RowGateway implements DbTableAwareInterface
{
    use \VuFind\Db\Table\DbTableAwareTrait;

    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct(\Laminas\Db\Adapter\Adapter $adapter)
    {
        parent::__construct('id', 'user_settings', $adapter);
    }

    /**
     * Get library prefixes from connected library cards
     *
     * @return array
     */
    public function getSavedInstitutions()
    {
        if (empty($this->saved_institutions)) {
            return [];
        }
        return explode(';', $this->saved_institutions);
    }

    /**
     * Get library prefixes from connected library cards
     *
     * @param array $institutions institutions
     *
     * @return void
     */
    public function setSavedInstitutions($institutions)
    {
        $this->saved_institutions = implode(';', $institutions);
    }
}
