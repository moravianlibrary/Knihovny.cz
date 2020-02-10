<?php
namespace KnihovnyCzConsole\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            \KnihovnyCzConsole\Controller\UtilController::class => \VuFind\Controller\AbstractBaseFactory::class,
        ],
        'aliases' => [
            \VuFindConsole\Controller\UtilController::class => \KnihovnyCzConsole\Controller\UtilController::class
        ],
    ],
];

$routes = [
    'util/expire_users' => 'util expire_users [--help|-h] [<daysOld>]',
    'util/harvest_ebooks' => 'util harvest_ebooks',
];

$routeGenerator = new \VuFindConsole\Route\RouteGenerator();
$routeGenerator->addRoutes($config, $routes);

return $config;
