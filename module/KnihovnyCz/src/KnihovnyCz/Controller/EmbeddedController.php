<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\Http\Header\ContentSecurityPolicy;

/**
 * Class EmbeddedController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
abstract class EmbeddedController extends SearchController
{
    /**
     * Prepare embedded layout
     *
     * @return void
     */
    protected function setLayout(): void
    {
        // Set main layout
        $this->layout('embedded/layout');

        // Disable session writes
        $this->disableSessionWrites();

        // Add value frame-ancestors * to Content-Security-Policy header
        $headers = $this->getResponse()->getHeaders();
        $cspHeader = $headers->get('Content-Security-Policy');
        if ($cspHeader === false) {
            $cspHeader = new ContentSecurityPolicy();
            $headers->addHeader($cspHeader);
        }
        if (is_iterable($cspHeader)) {
            $cspHeader = $cspHeader->current();
        }
        $cspHeader->setDirective('frame-ancestors', ['*']);
    }
}
