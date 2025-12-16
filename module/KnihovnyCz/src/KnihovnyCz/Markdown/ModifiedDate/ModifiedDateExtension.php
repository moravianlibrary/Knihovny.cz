<?php

declare(strict_types=1);

namespace KnihovnyCz\Markdown\ModifiedDate;

use KnihovnyCz\Content\GitLabService;
use KnihovnyCz\Content\PageLocator;
use Laminas\View\Renderer\PhpRenderer;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\Config\ConfigurationBuilderInterface;
use Nette\Schema\Expect;

/**
 * Class ModifiedDateExtension
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Markdown\ModifiedDate
 * @author   Pavel Pátek <pavel.patek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ModifiedDateExtension implements ConfigurableExtensionInterface
{
    /**
     * Constructor
     *
     * @param GitLabService $service     GitLab service
     * @param PageLocator   $pageLocator page locator
     * @param PhpRenderer   $renderer    renderer
     */
    public function __construct(
        protected GitLabService $service,
        protected PageLocator $pageLocator,
        protected PhpRenderer $renderer
    ) {
    }

    /**
     * Configure schema
     *
     * @param ConfigurationBuilderInterface $builder Configuration builder
     *
     * @return void
     */
    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        $builder->addSchema(
            'modified_date',
            Expect::structure(
                [
                    'placeholder' => Expect::string()->default('$ModifiedDate'),
                    'gitlab_api_url' => Expect::string()->default('https://gitlab.mzk.cz/api/v4/'),
                    'gitlab_project_id' => Expect::string()->default('49'),
                    'gitlab_branch' => Expect::string()->default('testing'),
                    'gitlab_base_path' => Expect::string()->default('data%2Fmain%2Ftemplates%2Fcontent%2F'),
                    'date_format' => Expect::string()->default('d.m.Y'),
                ],
            )
        );
    }

    /**
     * Register extension
     *
     * @param EnvironmentBuilderInterface $environment Commonmark environment
     *
     * @return void
     */
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $this->service->setApiUrl($environment->getConfiguration()->get('modified_date/gitlab_api_url'))
            ->setProjectId($environment->getConfiguration()->get('modified_date/gitlab_project_id'))
            ->setBranch($environment->getConfiguration()->get('modified_date/gitlab_branch'))
            ->setBasePath($environment->getConfiguration()->get('modified_date/gitlab_base_path'));

        $environment->addInlineParser(
            new ModifiedDateParser(
                $this->service,
                $this->pageLocator,
                $this->renderer,
                $environment->getConfiguration()->get('modified_date/placeholder'),
                $this->getPageName(),
                $environment->getConfiguration()->get('modified_date/date_format'),
            )
        );
    }

    /**
     * Get name of current page
     *
     * @return string
     */
    private function getPageName(): string
    {
        $requestedUri = $_SERVER['REQUEST_URI'];
        $pathParts = explode('/', $requestedUri);
        return $pathParts[count($pathParts) - 1];
    }
}
