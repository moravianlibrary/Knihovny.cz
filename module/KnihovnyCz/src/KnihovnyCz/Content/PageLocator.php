<?php

declare(strict_types=1);

namespace KnihovnyCz\Content;

use VuFind\Cache\CacheTrait;
use VuFind\Content\PageLocator as PageLocatorBase;
use VuFindHttp\HttpServiceAwareInterface;
use VuFindHttp\HttpServiceAwareTrait;

/**
 * Class PageLocator
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Content
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class PageLocator extends PageLocatorBase implements HttpServiceAwareInterface
{
    use HttpServiceAwareTrait;
    use CacheTrait;

    /**
     * Types/formats of content
     *
     * @var array $types
     */
    protected $types = [
        'md',
        'phtml',
    ];

    /**
     * Base URL for getting pages from repository available through HTTP
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Page constructor.
     *
     * @param \VuFindTheme\ThemeInfo $themeInfo       Theme information service
     * @param string                 $language        Current language
     * @param string                 $defaultLanguage Main configuration
     * @param string                 $baseUrl         Base HTTP repository URL
     */
    public function __construct(
        $themeInfo,
        $language,
        $defaultLanguage,
        string $baseUrl = ''
    ) {
        $this->baseUrl = $baseUrl;
        $this->baseUrl .= str_ends_with($baseUrl, '/') ? '' : '/';
        parent::__construct($themeInfo, $language, $defaultLanguage);
    }

    /**
     * Try to find a template using
     * 1) Current language
     * 2) Default language
     * 3) No language
     *
     * @param string $pathPrefix Subdirectory where the template should be located
     * @param string $pageName   Template name
     * @param string $pattern    Filesystem pattern
     *
     * @return \Generator Array generator with template options
     *                    (key equals matchType)
     */
    protected function getTemplateOptionsFromPattern(
        string $pathPrefix,
        string $pageName,
        string $pattern
    ): \Generator {
        yield 'urlLanguage' => $this->generateTemplateFromPattern(
            preg_replace('#https:/#', 'https://', $this->baseUrl . $pathPrefix),
            $pageName,
            $pattern,
            $this->language
        );
        if ($this->language != $this->defaultLanguage) {
            yield 'urlDefaultLanguage' => $this->generateTemplateFromPattern(
                $this->baseUrl . $pathPrefix,
                $pageName,
                $pattern,
                $this->defaultLanguage
            );
        }
        yield 'urlPageName' => $this->generateTemplateFromPattern(
            $this->baseUrl . $pathPrefix,
            $pageName,
            $pattern
        );
        yield from parent::getTemplateOptionsFromPattern(
            $pathPrefix,
            $pageName,
            $pattern
        );
    }

    /**
     * Try to find template information about desired page
     *
     * @param string $pathPrefix Subdirectory where the template should be located
     * @param string $pageName   Template name
     * @param string $pattern    Optional filesystem pattern
     *
     * @return array|null Null if template is not found or array with keys renderer
     * (type of template), path (full path of template), relativePath (relative
     * path within the templates directory), page (page name), theme,
     * matchType (see getTemplateOptionsFromPattern)
     */
    public function determineTemplateAndRenderer(
        $pathPrefix,
        $pageName,
        $pattern = null
    ) {
        if ($pattern === null) {
            $pattern = '%pathPrefix%/%pageName%{_%language%}';
        }

        $templates = $this->getTemplateOptionsFromPattern(
            $pathPrefix,
            $pageName,
            $pattern
        );

        foreach ($templates as $matchType => $template) {
            foreach ($this->types as $type) {
                $filename = "$template.$type";
                if ($type === 'md') {
                    $file = $this->checkFileAvailability($filename);
                    if (true === $file) {
                        return [
                            'renderer' => $type,
                            'path' => $filename,
                            'relativePath' => $filename,
                            'page' => basename($template),
                            'theme' => '',
                            'matchType' => $matchType,
                        ];
                    }
                }
                if ($type === 'phtml') {
                    $pathDetails = $this->themeInfo->findContainingTheme(
                        $filename,
                        $this->themeInfo::RETURN_ALL_DETAILS
                    );
                    if (null != $pathDetails) {
                        $relativeTemplatePath = preg_replace(
                            '"^templates/"',
                            '',
                            $pathDetails['relativePath']
                        );
                        return [
                            'renderer' => $type,
                            'path' => $pathDetails['path'],
                            'relativePath' => $relativeTemplatePath,
                            'page' => basename($template),
                            'theme' => $pathDetails['theme'],
                            'matchType' => $matchType,
                        ];
                    }
                }
            }
        }

        return
            parent::determineTemplateAndRenderer($pathPrefix, $pageName, $pattern);
    }

    /**
     * Check if file is available to download from HTTP based repository
     *
     * @param string $url URL of file to download
     *
     * @return bool
     */
    protected function checkFileAvailability(string $url): bool
    {
        $data = $this->getCachedData($url);
        if (null !== $data) {
            return true;
        }
        $client = $this->httpService->createClient($url);
        try {
            $response = $client->send();
        } catch (\Exception $exception) {
            return false;
        }
        if ($response->getStatusCode() === 200) {
            $this->putCachedData($url, $response->getBody(), 60);
            return true;
        }
        return false;
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
