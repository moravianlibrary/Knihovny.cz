<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

/**
 * Class Admin ILS drivers controller
 *
 * @category VuFind
 * @package  KnihovnyCz\Admin
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AdminIlsController extends \VuFindAdmin\Controller\AbstractAdmin
{
    /**
     * Status action
     *
     * @return mixed
     */
    public function statusAction()
    {
        $config = $this->getConfig('MultiBackend')->Drivers;
        $config = array_filter(
            $config->toArray(),
            function ($driver) {
                return $driver !== 'NoILS';
            }
        );
        $view = $this->createViewModel(['drivers' => $config]);
        $view->setTemplate('admin/ils/status');
        return $view;
    }
}
