<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

/**
 * Class FooterLink
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class FooterLink extends AbstractHelper
{
    /**
     * Invoke
     *
     * @param string $page     Page name
     * @param bool   $lightbox Should be content loaded into lightbox?
     *
     * @return string
     */
    public function __invoke(string $page, bool $lightbox = false): string
    {
        $view = $this->getView();
        return isset($view) ? $view->render(
            'Helpers/footer-link.phtml',
            [
                'page' => $page,
                'title' => 'link_' . $page,
                'lightbox' => $lightbox,
            ]
        ) : '';
    }
}
