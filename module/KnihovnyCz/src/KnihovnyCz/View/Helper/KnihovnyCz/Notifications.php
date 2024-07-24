<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use KnihovnyCz\Db\Service\NotificationsServiceInterface;

/**
 * Class Notifications
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Notifications extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Priorities specification
     *
     * @var array|string[]
     */
    protected array $priorities = [
        1 => 'success',
        2 => 'info',
        3 => 'warning',
        4 => 'danger',
    ];

    /**
     * Constructor
     *
     * @param NotificationsServiceInterface $notificationsService Notifications service
     * @param string                        $locale               Current locale
     */
    public function __construct(
        protected NotificationsServiceInterface $notificationsService,
        protected string $locale
    ) {
    }

    /**
     * Get priority class
     *
     * @param int $priority Priority identifier
     *
     * @return string
     */
    public function getPriorityClass(int $priority): string
    {
        return $this->priorities[$priority] ?? 'info';
    }

    /**
     * Get priorities specifications
     *
     * @return array|string[]
     */
    public function getPriorityData(): array
    {
        return $this->priorities;
    }

    /**
     * Render current notifications
     *
     * @return string
     */
    public function renderCurrentNotifications(): string
    {
        $notifications = $this->notificationsService->getActiveNotifications($this->locale);
        $view = $this->getView();
        $html = '<div class="row"><div class="col-sm-12">';
        foreach ($notifications as $notification) {
            $html .= $view->render('notification/template.phtml', ['data' => $notification]);
        }
        $html .= '</div></div>';
        return $html;
    }
}
