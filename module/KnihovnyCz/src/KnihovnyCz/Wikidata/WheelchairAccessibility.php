<?php

namespace KnihovnyCz\Wikidata;

/**
 * Enum WheelchairAccessibility
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Wikidata
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
enum WheelchairAccessibility: string
{
    /*
     * See https://www.wikidata.org/wiki/Property:P2846
     */
    case Accessible = 'http://www.wikidata.org/entity/Q24192067';
    case AccessibleWithHelp = 'http://www.wikidata.org/entity/Q24192068';
    case Inaccessible = 'http://www.wikidata.org/entity/Q24192069';
    case PartiallyAccessible = 'http://www.wikidata.org/entity/Q63731120';
    case PartiallyAccessibleWithHelp = 'http://www.wikidata.org/entity/Q63731151';
    case AccessibleForVisualImpairment = 'http://www.wikidata.org/entity/Q112946571';
    case InaccessibleForVisualImpairment = 'http://www.wikidata.org/entity/Q112946915';

    /**
     * Get human readable description of a value
     *
     * @return string
     */
    public function getDescription(): string
    {
        return match ($this) {
            WheelchairAccessibility::Accessible => 'Accessible',
            WheelchairAccessibility::AccessibleWithHelp => 'Accessible with help',
            WheelchairAccessibility::Inaccessible => 'Inaccessible',
            WheelchairAccessibility::PartiallyAccessible => 'Partially accessible',
            WheelchairAccessibility::PartiallyAccessibleWithHelp => 'Partially accessible with help',
            WheelchairAccessibility::AccessibleForVisualImpairment => 'Accessible for visual impairments',
            WheelchairAccessibility::InaccessibleForVisualImpairment => 'Inaccessible for visual impairments',
        };
    }

    /**
     * Get icon name for a value
     *
     * @return string
     */
    public function getIcon(): string
    {
        return match ($this) {
            WheelchairAccessibility::Accessible => 'wheelchair-accessible',
            WheelchairAccessibility::AccessibleWithHelp => 'wheelchair-accessible-with-help',
            WheelchairAccessibility::Inaccessible => 'wheelchair-inaccessible',
            WheelchairAccessibility::PartiallyAccessible => 'wheelchair-partially-accessible',
            WheelchairAccessibility::PartiallyAccessibleWithHelp => 'wheelchair-partially-accessible-with-help',
            WheelchairAccessibility::AccessibleForVisualImpairment => 'wheelchair-accessible-for-visual-impairment',
            WheelchairAccessibility::InaccessibleForVisualImpairment => 'wheelchair-inaccessible-for-visual-impairment',
        };
    }
}
