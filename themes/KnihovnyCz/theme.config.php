<?php
return [
    'extends' => 'bootstrap3',
    'helpers' => [
        'factories' => [
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'KnihovnyCz\View\Helper\KnihovnyCz\RecordDataFormatterFactory',
        ],
        'invokables' => [
            \KnihovnyCz\View\Helper\KnihovnyCz\LibrariesApiLookfor::class,
        ],
        'aliases' => [
            'recordDataFormatter' => 'VuFind\View\Helper\Root\RecordDataFormatter',
            'librariesApiLookfor' => \KnihovnyCz\View\Helper\KnihovnyCz\LibrariesApiLookfor::class
        ],
    ],
    'favicon' => 'icon-knihovny.png',
];
