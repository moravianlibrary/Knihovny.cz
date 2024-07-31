<?php

namespace KnihovnyCzConsole\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'command' => [
                'factories' => [
                    \KnihovnyCzConsole\Command\Util\ClearCacheCommand::class => \KnihovnyCzConsole\Command\Util\ClearCacheCommandFactory::class,
                    \KnihovnyCzConsole\Command\Util\ExpireUsersCommand::class => \KnihovnyCzConsole\Command\Util\ExpireUsersCommandFactory::class,
                    \KnihovnyCzConsole\Command\Util\ExpireCsrfTokensCommand::class => \KnihovnyCzConsole\Command\Util\ExpireCsrfTokensCommandFactory::class,
                    \KnihovnyCzConsole\Command\Util\UpdateResourcesFromSolrCommand::class => \KnihovnyCzConsole\Command\Util\UpdateResourcesFromSolrCommandFactory::class,
                    \KnihovnyCzConsole\Command\Util\UpdateRecordStatus::class => \KnihovnyCzConsole\Command\Util\UpdateRecordStatusFactory::class,
                    \KnihovnyCzConsole\Command\Util\MigrateSearchCommand::class => \KnihovnyCzConsole\Command\Util\MigrateSearchCommandFactory::class,
                ],
                'aliases' => [
                    'util/clear_cache' => \KnihovnyCzConsole\Command\Util\ClearCacheCommand::class,
                    'util/expire_users' => \KnihovnyCzConsole\Command\Util\ExpireUsersCommand::class,
                    'util/expire_csrf_tokens' => \KnihovnyCzConsole\Command\Util\ExpireCsrfTokensCommand::class,
                    'util/update_resources_from_solr' => \KnihovnyCzConsole\Command\Util\UpdateResourcesFromSolrCommandFactory::class,
                    'util/update_record_status' => \KnihovnyCzConsole\Command\Util\UpdateRecordStatusFactory::class,
                    'util/migrate_search' => \KnihovnyCzConsole\Command\Util\MigrateSearchCommand::class,
                ],
            ],
        ],
    ],
];

return $config;
