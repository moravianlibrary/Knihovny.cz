<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

/**
 * Context Help Helper
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ContextHelp extends AbstractHelper
{
    /**
     * Render context help
     *
     * @param string $page Context help page identifier
     * @param string $type Context help type
     *
     * @return string
     */
    public function __invoke(string $page, string $type = ''): string
    {
        $classes[] = 'context-help-link';

        if ($type !== '') {
            $classes[] = 'context-help-link-' . $type;
        }

        $view = $this->getView();
        return isset($view) ? $view->render(
            'Helpers/context-help.phtml',
            [
                'page' => $page,
                'classes' => implode(' ', $classes),
            ]
        ) : '';
    }
}
