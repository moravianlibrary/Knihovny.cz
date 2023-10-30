<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;
use VuFindTheme\ResourceContainer;

/**
 * Setup Embedded Theme Resources
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SetupEmbeddedThemeResources extends AbstractHelper
{
    /**
     * Theme resource container
     *
     * @var \VuFindTheme\ResourceContainer
     */
    protected ResourceContainer $container;

    /**
     * Constructor
     *
     * @param \VuFindTheme\ResourceContainer $container Theme resource container
     */
    public function __construct(ResourceContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Set up items based on contents of theme resource container.
     *
     * @return void
     */
    public function __invoke(): void
    {
        $this->addLinks();
    }

    /**
     * Add links to header.
     *
     * @return void
     */
    protected function addLinks(): void
    {
        $headLink = $this->getView()->plugin('headLink');

        $favicon = $this->container->getFavicon();
        if (!empty($favicon)) {
            $imageLink = $this->getView()->plugin('imageLink');
            $headLink(
                [
                    'href' => $imageLink($favicon),
                    'type' => 'image/x-icon', 'rel' => 'shortcut icon',
                ]
            );
        }
    }
}
