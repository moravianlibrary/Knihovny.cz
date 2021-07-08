<?php
namespace KnihovnyCzApi\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            \KnihovnyCzApi\Controller\SearchApiController::class => \KnihovnyCzApi\Controller\SearchApiControllerFactory::class,
        ],
        'aliases' => [
            \VuFindApi\Controller\SearchApiController::class => \KnihovnyCzApi\Controller\SearchApiController::class,
        ]
    ],
    'service_manager' => [
        'factories' => [
            \KnihovnyCzApi\Formatter\ItemFormatter::class => \KnihovnyCzApi\Formatter\ItemFormatterFactory::class,
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
                    ]
                ]
            ],
        ],
    ],
];

return $config;
