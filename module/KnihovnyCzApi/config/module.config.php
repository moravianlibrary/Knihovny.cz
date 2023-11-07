<?php

namespace KnihovnyCzApi\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            \KnihovnyCzApi\Controller\SearchApiController::class => \KnihovnyCzApi\Controller\SearchApiControllerFactory::class,
            \KnihovnyCzApi\Controller\Search2ApiController::class => \VuFindApi\Controller\Search2ApiControllerFactory::class,
        ],
        'aliases' => [
            \VuFindApi\Controller\SearchApiController::class => \KnihovnyCzApi\Controller\SearchApiController::class,
            \VuFindApi\Controller\Search2ApiController::class => \KnihovnyCzApi\Controller\Search2ApiController::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            \KnihovnyCzApi\Formatter\ItemFormatter::class => \KnihovnyCzApi\Formatter\ItemFormatterFactory::class,
            \KnihovnyCzApi\Formatter\RecordFormatter::class => \VuFindApi\Formatter\RecordFormatterFactory::class,
        ],
        'aliases' => [
            \VuFindApi\Formatter\RecordFormatter::class => \KnihovnyCzApi\Formatter\RecordFormatter::class,
        ],
    ],
    'router' => [
        'routes' => [
            'itemApiv1' => [
                'type' => 'Laminas\Router\Http\Literal',
                'verb' => 'get,post,options',
                'options' => [
                    'route'    => '/api/v1/item',
                    'defaults' => [
                        'controller' => 'SearchApi',
                        'action'     => 'item',
                    ],
                ],
            ],
            'search2Apiv1' => [
                'type' => 'Laminas\Router\Http\Literal',
                'verb' => 'get,post,options',
                'options' => [
                    'route'    => '/api/v1/libraries/search',
                    'defaults' => [
                        'controller' => 'Search2Api',
                        'action'     => 'search',
                    ],
                ],
            ],
            'record2Apiv1' => [
                'type' => 'Laminas\Router\Http\Literal',
                'verb' => 'get,post,options',
                'options' => [
                    'route'    => '/api/v1/libraries/record',
                    'defaults' => [
                        'controller' => 'Search2Api',
                        'action'     => 'record',
                    ],
                ],
            ],
        ],
    ],
];

return $config;
