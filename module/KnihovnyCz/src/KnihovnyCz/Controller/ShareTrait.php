<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\View\Model\ViewModel;

/**
 * Share trait
 *
 * @category VuFind
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Site
 */
trait ShareTrait
{
    /**
     * Share record action
     *
     * @return ViewModel
     */
    public function shareAction()
    {
        $view = $this->createViewModel();
        $view->setTemplate('record/share');
        return $view;
    }
}
