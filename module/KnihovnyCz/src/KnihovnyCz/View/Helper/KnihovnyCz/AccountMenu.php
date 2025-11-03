<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

/**
 * Account menu view helper
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AccountMenu extends \VuFind\View\Helper\Root\AccountMenu
{
    /**
     * Render account menu
     *
     * @param ?string $activeItem The name of current active item (optional)
     * @param string  $idPrefix   Element ID prefix
     *
     * @return string
     */
    public function render(?string $activeItem = null, string $idPrefix = ''): string
    {
        $contextHelper = $this->getView()->plugin('context');
        $menu = $this->getMenu();

        if ($idPrefix === 'header_') {
            return $contextHelper->renderInContext(
                'myresearch/menu-header.phtml',
                [
                    'menu' => $menu,
                    'active' => $activeItem,
                    'idPrefix' => $idPrefix,
                    // set items for backward compatibility, might be removed in future releases
                    'items' => $menu['Account']['MenuItems'],
                ]
            );
        }

        return $contextHelper->renderInContext(
            'myresearch/menu.phtml',
            [
                'menu' => $menu,
                'active' => $activeItem,
                'idPrefix' => $idPrefix,
                // set items for backward compatibility, might be removed in future releases
                'items' => $menu['Account']['MenuItems'],
            ]
        );
    }
}
