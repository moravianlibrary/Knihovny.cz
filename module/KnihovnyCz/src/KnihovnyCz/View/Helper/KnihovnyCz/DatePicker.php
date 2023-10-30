<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for date picker.
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class DatePicker extends AbstractHelper
{
    protected bool $initialized = false;

    /**
     * Render context help
     *
     * @param string? $name form element name
     *
     * @return string
     */
    public function __invoke(string $name = null): string
    {
        $result = $this->getView()->render('Helpers/date-picker.phtml', [
            'formName' => $name,
            'initialized' => $this->initialized,
        ]);
        $this->initialized = true;
        return $result;
    }
}
