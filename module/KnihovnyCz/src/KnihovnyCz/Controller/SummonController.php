<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\Mvc\MvcEvent;

/**
 * Class SummonController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SummonController extends \VuFind\Controller\SummonController
{
    use GeoIpAccessTrait;

    /**
     * Register the default events for this controller
     *
     * @return void
     */
    protected function attachDefaultListeners()
    {
        parent::attachDefaultListeners();
        $events = $this->getEventManager();
        $events->attach(
            MvcEvent::EVENT_DISPATCH,
            [$this, 'checkGeoIP'],
            1000
        );
    }
}
