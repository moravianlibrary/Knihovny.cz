<?php
namespace KnihovnyCzConsole\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'command' => [
                'factories' => [
                    \KnihovnyCzConsole\Command\Util\ExpireUsersCommand::class => \KnihovnyCzConsole\Command\Util\ExpireUsersCommandFactory::class,
                    \KnihovnyCzConsole\Command\Util\HarvestEbooksCommand::class => \KnihovnyCzConsole\Command\Util\HarvestEbooksCommandFactory::class,
                ],
                'aliases' => [
                    'util/expire_users' => \KnihovnyCzConsole\Command\Util\ExpireUsersCommand::class,
                    'util/harvest_ebooks' => \KnihovnyCzConsole\Command\Util\HarvestEbooksCommand::class,
                ]
            ]
        ]
    ]
];

return $config;
