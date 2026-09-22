<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;
use VuFind\I18n\Translator\TranslatorAwareTrait;

/**
 * Class InstitutionsSorter
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class InstitutionsSorter extends AbstractHelper
{
    use TranslatorAwareTrait;

    /**
     * Sort institutions by order and text. Current locale from translator is
     * used for text comparison.
     *
     * @param array $institutions Institutions
     *
     * @return void
     */
    public function sort(array &$institutions): void
    {
        $collator = new \Collator($this->translator->getLocale());
        usort($institutions, function ($a, $b) use ($collator) {
            $order = $a['order'] <=> $b['order'];
            return ($order != 0) ? $order : $collator->compare($a['text'], $b['text']);
        });
    }
}
