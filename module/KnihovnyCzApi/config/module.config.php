<?php
namespace KnihovnyCzApi\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            \KnihovnyCzApi\Controller\Search2ApiController::class => \VuFindApi\Controller\Search2ApiControllerFactory::class,
        ],
        'aliases' => [
            \VuFindApi\Controller\Search2ApiController::class => \KnihovnyCzApi\Controller\Search2ApiController::class
        ],
    ],
];

return $config;
