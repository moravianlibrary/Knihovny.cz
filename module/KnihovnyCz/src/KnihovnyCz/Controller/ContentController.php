<?php
declare(strict_types=1);

/**
 * Class ContentController
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2022.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

use Laminas\View\Model\ViewModel;
use VuFind\Cache\CacheTrait;
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
class ContentController extends \VuFind\Controller\ContentController
    implements HttpServiceAwareInterface
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
            "",
            "content$suffix"
        );
    }
}
