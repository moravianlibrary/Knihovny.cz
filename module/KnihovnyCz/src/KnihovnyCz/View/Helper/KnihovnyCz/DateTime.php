<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use VuFind\View\Helper\Root\DateTime as Base;

/**
 * View helper for formatting dates and times
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class DateTime extends Base
{
    /**
     * Return display date format for JQuery
     *
     * @return string
     */
    public function getDisplayDateFormatForJquery(): string
    {
        $dueDateHelpString
            = $this->converter->convertToDisplayDate('m-d-y', '11-22-3333');
        $search = ['11', '22', '3333'];
        $replace = [
            $this->getView()->translate('mm'),
            $this->getView()->translate('dd'),
            $this->getView()->translate('yy'),
        ];
        return str_replace($search, $replace, $dueDateHelpString);
    }
}
