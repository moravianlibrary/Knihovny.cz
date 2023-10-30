<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\View\Model\ViewModel;
use VuFind\Cache\CacheTrait;
use VuFind\Controller\ContentController as ContentControllerBase;
use VuFindHttp\HttpServiceAwareInterface;
use VuFindHttp\HttpServiceAwareTrait;

/**
 * Class ContentController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ContentController extends ContentControllerBase implements
    HttpServiceAwareInterface
{
    use HttpServiceAwareTrait;
    use CacheTrait;

    /**
     * Get ViewModel for markdown based page
     *
     * @param string $page Page name/route (if applicable)
     * @param string $path Full path to file with content (if applicable)
     *
     * @return ViewModel
     */
    protected function getViewForMd(string $page, string $path): ViewModel
    {
        $data = $this->getCachedData($path);
        if (null === $data) {
            try {
                $client = $this->httpService->createClient($path);
                $response = $client->send();
                $data = $response->getBody();
                $this->putCachedData($path, $response->getBody(), 60);
            } catch (\Exception $exception) {
                return $this->notFoundAction();
            }
        }

        $view = $this->createViewModel(['data' => $data]);
        $view->setTemplate('content/markdown');
        return $view;
    }

    /**
     * Method to ensure uniform cache keys for cached VuFind objects.
     *
     * @param string|null $suffix Optional suffix that will get appended to the
     * object class name calling getCacheKey()
     *
     * @return string
     */
    protected function getCacheKey($suffix = null)
    {
        return preg_replace(
            "/([^a-z0-9_\+\-])+/Di",
            '',
            "content$suffix"
        );
    }
}
