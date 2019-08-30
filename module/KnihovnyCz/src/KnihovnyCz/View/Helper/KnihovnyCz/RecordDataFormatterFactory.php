<?php
/**
 * Class RecordDataFormatterFactory
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2019.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Interop\Container\ContainerInterface;
use VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder;

class RecordDataFormatterFactory extends \VuFind\View\Helper\Root\RecordDataFormatterFactory
{

    /**
     * Create the helper.
     *
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        $helper = parent::__invoke($container, $requestedName, $options);
        $helper->setDefaults('library', [$this, 'getDefaultLibraryCoreSpecs']);
        return $helper;
    }

    /**
     * @return array;
     */
    public function getDefaultLibraryCoreSpecs()
    {
        $spec = new SpecBuilder();
        $spec->setTemplateLine('Book search', 'getBookSearchFilter',
            'search_in_library_link.phtml', ['context' => ['icon' => 'pr-interface-circlerighttrue']]);

        $spec->setLine('Address', 'getLibraryAddress', null,
            ['context' => ['icon' => 'pr-location-pinmap5']]);

        $spec->setTemplateLine('Opening hours', 'getLibraryHoursArray',
            'opening_hours.phtml', ['context' => ['icon' => 'pr-interface-clocktime']]);

        $spec->setLine('Additional information', 'getLibNote', null,
            ['context' => ['icon' => 'pr-interface-circlerighttrue']]);

        $spec->setLine('Additional information2', 'getLibNote2', null,
            ['context' => ['icon' => 'pr-interface-circlerighttrue']]);

        $spec->setTemplateLine('Web sites', 'getLibUrlArray',
            'library_links.phtml', ['context' => ['icon' => 'pr-web-browseinternetnetwork']]);

        $spec->setLine('Library type', 'getType', null,
            ['context' => ['icon' => 'pr-interface-circlerighttrue']]);

        $spec->setTemplateLine('Regional library', 'getRegLibrary',
            'regional_library.phtml', ['context' => ['icon' => 'pr-interface-circlerighttrue']]);

        $spec->setLine('Interlibrary loan', 'getMvs', null,
            ['context' => ['icon' => 'pr-interface-circlerighttrue']]);

        return $spec->getArray();
    }
}