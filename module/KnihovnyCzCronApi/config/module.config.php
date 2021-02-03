<?php
namespace KnihovnyCzCronApi\Module\Configuration;

$secret = getenv('CRONJOB_SECRET');

$config = [
    'router' => [
        'routes' => [
            'default' => [
                'type'    => 'Laminas\Router\Http\Segment',
                'options' => [
                    'route'    => '/cronjobs-' . $secret . '/[:action]',
                    'constraints' => [
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => 'cronjob',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            \KnihovnyCzCronApi\Controller\Cronjob::class => \VuFind\Controller\AbstractBaseFactory::class,
        ],
        'aliases' => [
            'cronjob' => \KnihovnyCzCronApi\Controller\Cronjob::class,
        ]
    ],
];

return $config;
