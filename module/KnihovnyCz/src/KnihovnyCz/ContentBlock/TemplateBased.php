<?php

declare(strict_types=1);

namespace KnihovnyCz\ContentBlock;

use VuFind\Cache\CacheTrait;
use VuFind\ContentBlock\TemplateBased as TemplateBasedBase;
use VuFindHttp\HttpServiceAwareInterface;
use VuFindHttp\HttpServiceAwareTrait;

/**
 * Class TemplateBased
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ContentBlock
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class TemplateBased extends TemplateBasedBase implements HttpServiceAwareInterface
{
    use HttpServiceAwareTrait;
    use CacheTrait;

    /**
     * Return context array for markdown
     *
     * @param string $relativePath Relative path to template
     * @param string $path         Full path of template file
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getContextForMd(string $relativePath, string $path): array
    {
        $data = $this->getCachedData($path);
        if (null === $data) {
            try {
                $client = $this->httpService->createClient($path);
                $response = $client->send();
                $data = $response->getBody();
                $this->putCachedData($path, $response->getBody(), 60);
            } catch (\Exception $exception) {
                $data = '';
            }
        }
        return [
            'template' => 'ContentBlock/TemplateBased/markdown',
            'data' => $data,
        ];
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
