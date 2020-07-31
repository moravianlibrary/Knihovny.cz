<?php
return [
    'extends' => 'bootstrap3',
    'helpers' => [
        'factories' => [
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'KnihovnyCz\View\Helper\KnihovnyCz\RecordDataFormatterFactory',
        ],
        'aliases' => [
            'recordDataFormatter' => 'VuFind\View\Helper\Root\RecordDataFormatter',
        ],
    ],
    'favicon' => 'favicon.ico',
];
