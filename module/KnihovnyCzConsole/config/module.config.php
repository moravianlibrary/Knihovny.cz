<?php
namespace KnihovnyCzConsole\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'command' => [
                'factories' => [
                    \KnihovnyCzConsole\Command\Expire\UsersCommand::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                    \KnihovnyCzConsole\Command\Harvest\EbooksCommand::class => \KnihovnyCzConsole\Command\Harvest\EbooksCommandFactory::class,
                ],
                'aliases' => [
                    'util/expire_users' => \KnihovnyCzConsole\Command\Expire\UsersCommand::class,
                    'util/harvest_ebooks' => \KnihovnyCzConsole\Command\Harvest\EbooksCommand::class,
                ]
            ]
        ]
    ]
];

return $config;
