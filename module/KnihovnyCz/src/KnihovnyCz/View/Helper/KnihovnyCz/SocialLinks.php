<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

/**
 * Social links view helper
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SocialLinks extends AbstractHelper
{
    /**
     * Constructor
     *
     * @param array  $config SocialLinks configuration
     * @param string $locale Current locale
     */
    public function __construct(
        protected array $config,
        protected string $locale
    ) {
    }

    /**
     * Get available menu items
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->config['Items'][$this->locale] ?? [];
    }

    /**
     * Render helper
     *
     * @return string
     */
    public function render(): string
    {
        $contextHelper = $this->getView()->plugin('context');
        return $contextHelper->renderInContext(
            'sociallinks.phtml',
            ['items' => $this->getItems()]
        );
    }
}
