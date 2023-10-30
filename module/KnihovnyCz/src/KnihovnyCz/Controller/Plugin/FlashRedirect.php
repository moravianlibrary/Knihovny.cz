<?php

namespace KnihovnyCz\Controller\Plugin;

use GuzzleHttp\Psr7\Query;
use KnihovnyCz\Session\NullSessionManager;
use Laminas\Http\Response;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use VuFind\Controller\Plugin\AbstractRequestBase;

/**
 * VuFind Flash Redirect
 *
 * @category VuFind
 * @package  Controller_Plugins
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class FlashRedirect extends AbstractRequestBase
{
    public const NAMESPACES = [
        FlashMessenger::NAMESPACE_DEFAULT,
        FlashMessenger::NAMESPACE_ERROR,
        FlashMessenger::NAMESPACE_INFO,
        FlashMessenger::NAMESPACE_SUCCESS,
        FlashMessenger::NAMESPACE_WARNING,
    ];

    public const PREFIX = 'flash_';

    /**
     * Restore messages in flashMessenger from parameters in URL
     *
     * @return void
     */
    public function restore()
    {
        $controller = $this->getController();
        if (!$controller) {
            return;
        }
        $flashMessenger = $controller->flashMessenger();
        $flashMessenger->setSessionManager(new NullSessionManager());
        foreach (self::NAMESPACES as $namespace) {
            $messages = $controller->params()->fromQuery('flash_' . $namespace);
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    $flashMessenger->addMessage($message, $namespace);
                }
            } elseif (is_string($messages)) {
                $flashMessenger->addMessage($messages, $namespace);
            }
        }
    }

    /**
     * Generate redirect response based on given URL and add to URL
     * parameters with messages from flashMessenger
     *
     * @param string $url URL
     *
     * @return Response
     */
    public function toUrl($url)
    {
        $newUrl = $this->appendFlashMessages($url);
        $controller = $this->getController();
        return $controller
            ? $controller->redirect()->toUrl($newUrl) : new Response();
    }

    /**
     * Add to URL parameters with messages from flashMessenger
     *
     * @param string $url URL
     *
     * @return string
     */
    protected function appendFlashMessages($url)
    {
        $controller = $this->getController();
        if (!$controller) {
            return $url;
        }
        $flashMessenger = $controller->flashMessenger();
        $queryPart = (string)parse_url($url, PHP_URL_QUERY) ?? '';
        $params = Query::parse($queryPart);
        foreach (self::NAMESPACES as $namespace) {
            if ($flashMessenger->hasCurrentMessages($namespace)) {
                $params['flash_' . $namespace] = $flashMessenger
                    ->getCurrentMessages($namespace);
            }
        }
        $params = http_build_query($params);
        return strtok($url, '?') . '?' . $params;
    }
}
