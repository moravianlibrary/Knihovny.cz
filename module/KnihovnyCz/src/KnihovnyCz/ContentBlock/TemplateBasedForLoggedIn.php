<?php

declare(strict_types=1);

namespace KnihovnyCz\ContentBlock;

use KnihovnyCz\Content\PageLocator;

/**
 * Class TemplateBasedForLoggedIn
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\ContentBlock
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class TemplateBasedForLoggedIn extends TemplateBased
{
    /**
     * If user is logged in
     *
     * @var bool
     */
    protected bool $isLoggedIn;

    /**
     * TemplateBasedHiddenForLoggedIn constructor
     *
     * @param PageLocator $pageLocator Content page locator service
     * @param bool        $isLoggedIn  If user is logged in
     */
    public function __construct(
        PageLocator $pageLocator,
        bool $isLoggedIn = false
    ) {
        $this->isLoggedIn = $isLoggedIn;

        parent::__construct($pageLocator);
    }

    /**
     * Return context variables used for rendering the block's template.
     *
     * @param string $pathPrefix Subdirectory where the template should be located
     * @param string $page       Template name (defaults to config value if unset)
     * @param string $pattern    Filesystem pattern (see PageLocator)
     *
     * @return array
     */
    public function getContext(
        $pathPrefix = 'templates/ContentBlock/TemplateBased/',
        $page = null,
        $pattern = null
    ): array {
        if (!$this->isLoggedIn) {
            return [
                'template' => 'ContentBlock/TemplateBased/markdown',
                'data' => '',
            ];
        }

        return parent::getContext($pathPrefix, $page, $pattern);
    }
}
