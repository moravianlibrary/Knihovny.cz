<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use KnihovnyCz\Db\Service\NotificationsServiceInterface;
use Laminas\Http\Response;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;
use VuFind\I18n\Locale\LocaleSettings;

/**
 * Class NotificationController
 *
 * @category VuFind
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class NotificationController extends AbstractBase
{
    /**
     * Notifications db service
     *
     * @var NotificationsServiceInterface|mixed $notificationsService
     */
    protected NotificationsServiceInterface $notificationsService;

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service locator
     */
    public function __construct(ServiceLocatorInterface $sm)
    {
        parent::__construct($sm);
        $this->accessPermission = 'access.Notification';
        $dbServiceManager = $sm->get(\VuFind\Db\Service\PluginManager::class);
        $this->notificationsService = $dbServiceManager->get(NotificationsServiceInterface::class);
    }

    /**
     * Home action
     *
     * @return Response|ViewModel
     */
    public function homeAction(): Response|ViewModel
    {
        $view = $this->createViewModel();
        $settings = $this->serviceLocator->get(LocaleSettings::class);
        $language = $settings->getUserLocale();
        $view->setVariable('data', $this->notificationsService->getAllNotifications($language));
        return $view;
    }

    /**
     * Edit notification action
     *
     * @return Response|ViewModel
     */
    public function editAction(): Response|ViewModel
    {
        $id = $this->params()->fromRoute('id');
        $notification = null;
        if ($id !== 'NEW') {
            $id = intval($id);
            $notification = $this->notificationsService->getById($id);
        }
        $params = $this->params()->fromQuery() + $this->params()->fromPost();
        $authManager = $this->getAuthManager();
        if (($params['submit'] ?? false) && $authManager->isValidCsrfHash($params['csrf'] ?? '')) {
            if ($id === 'NEW') {
                $notification = $this->notificationsService->createEntity();
            }
            $user = $authManager->getUserObject();
            $notification->setAuthor($user);
            if (isset($params['visibility'])) {
                $params['visibility'] = intval($params['visibility']);
            }
            if (isset($params['priority'])) {
                $params['priority'] = intval($params['priority']);
            }
            $paramsToSet = ['content', 'language', 'visibility', 'priority'];
            foreach ($paramsToSet as $paramName) {
                if (isset($params[$paramName])) {
                    $method = 'set' . ucfirst($paramName);
                    $notification->$method($params[$paramName]);
                }
            }
            $notification->save();
            return $this->redirect()->toRoute('notifications');
        }
        $view = $this->createViewModel();
        $view->setVariable('data', $notification);
        return $view;
    }

    /**
     * Delete action
     *
     * @return Response|ViewModel
     * @throws \Exception
     */
    public function deleteAction(): Response|ViewModel
    {
        $id = $this->params()->fromRoute('id');
        $id = intval($id);
        $params = $this->params()->fromQuery() + $this->params()->fromPost();
        $confirm = $params['confirm'] ?? false;
        $csrf = $params['csrf'] ?? '';
        if ($confirm && $this->getAuthManager()->isValidCsrfHash($csrf)) {
            $success =  $this->notificationsService->deleteById($id);
            if (!$success) {
                throw new \Exception('Could not remove notification');
            }
        }
        return $this->createViewModel();
    }
}
