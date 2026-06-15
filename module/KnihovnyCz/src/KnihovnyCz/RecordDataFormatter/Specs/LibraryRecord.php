<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordDataFormatter\Specs;

use VuFind\RecordDataFormatter\Specs\AbstractBase;
use VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder;

/**
 * Class LibraryRecord
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDataFormatter
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class LibraryRecord extends AbstractBase
{
    /**
     * Constructor
     *
     * @param array $config Config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * Initialize specs.
     *
     * @return void
     */
    protected function init(): void
    {
        $this->setDefaults('core', [$this, 'getDefaultCoreSpecs']);
    }

    /**
     * Library record detail display specifications
     *
     * @return array
     */
    protected function getDefaultCoreSpecs(): array
    {
        return new SpecBuilder()
            ->setTemplateLine(
                'Book search',
                'getBookSearchFilter',
                'search_in_library_link.phtml',
                ['context' => ['icon' => 'search', 'heading' => false]]
            )->setLine(
                'Address',
                'getLibraryAddress',
                null,
                ['context' => ['icon' => 'map-marker', 'content-class' => 'library-large', 'heading' => false]]
            )->setTemplateLine(
                'Opening hours',
                'getLibraryHours',
                'opening_hours.phtml',
                ['context' => ['icon' => 'opening-hours']]
            )->setLine('Additional information', 'getLibNote')
            ->setLine('Additional information2', 'getLibNote2')
            ->setTemplateLine(
                'Web sites',
                'getWebsites',
                'library_websites.phtml',
                ['context' => ['icon' => 'website']]
            )->setLine('Library type', 'getType')
            ->setTemplateLine('Regional library', 'getRegLibrary', 'regional_library.phtml')
            ->setLine('Interlibrary loan', 'getMvs')
            ->setTemplateLine(
                'Wheelchair accessibility',
                'getWheelchairAccessibility',
                'wheelchair-accessibility.phtml'
            )->getArray();
    }
}
