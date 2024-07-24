# CREATE DATABASE vufind_[view];
# USE vufind_[view];

-- Adminer 4.8.1 MySQL 5.5.5-10.7.4-MariaDB-1:10.7.4+maria~focal dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `auth_hash`;
CREATE TABLE `auth_hash` (
                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                           `session_id` varchar(128) DEFAULT NULL,
                           `hash` varchar(255) NOT NULL DEFAULT '',
                           `type` varchar(50) DEFAULT NULL,
                           `data` mediumtext DEFAULT NULL,
                           `created` timestamp NOT NULL DEFAULT current_timestamp(),
                           PRIMARY KEY (`id`),
                           UNIQUE KEY `hash_type` (`hash`,`type`),
                           KEY `session_id` (`session_id`),
                           KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `change_tracker`;
CREATE TABLE `change_tracker` (
                                `core` varchar(30) NOT NULL,
                                `id` varchar(120) NOT NULL,
                                `first_indexed` datetime DEFAULT NULL,
                                `last_indexed` datetime DEFAULT NULL,
                                `last_record_change` datetime DEFAULT NULL,
                                `deleted` datetime DEFAULT NULL,
                                PRIMARY KEY (`core`,`id`),
                                KEY `deleted_index` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP VIEW IF EXISTS `comments`;
CREATE TABLE `comments` (`id` int(11), `user_id` int(11), `resource_id` int(11), `comment` mediumtext, `created` datetime);


SET NAMES utf8mb4;

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `file_id` int(11) NOT NULL COMMENT 'Soubor',
                        `section_id` int(11) NOT NULL COMMENT 'Sekce',
                        `item_id` int(11) NOT NULL COMMENT 'Položka (klíč)',
                        `array_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Klíč pole (nepovinné)',
                        `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hodnota',
                        `order` int(11) NOT NULL COMMENT 'Pořadí',
                        `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Aktivní?',
                        PRIMARY KEY (`id`),
                        KEY `file_id` (`file_id`),
                        KEY `section_id` (`section_id`),
                        KEY `order` (`order`),
                        KEY `active` (`active`),
                        KEY `item_id` (`item_id`),
                        CONSTRAINT `config_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `config_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                        CONSTRAINT `config_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `config_files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                        CONSTRAINT `config_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `config_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Konfigurace';


DROP TABLE IF EXISTS `config_files`;
CREATE TABLE `config_files` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `file_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Název souboru',
                              PRIMARY KEY (`id`),
                              KEY `name` (`file_name`(190))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Konfigurační soubory';


DROP TABLE IF EXISTS `config_items`;
CREATE TABLE `config_items` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Název položky',
                              `type` enum('string','array') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string' COMMENT 'Typ',
                              PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Konfigurační položky';


DROP TABLE IF EXISTS `config_sections`;
CREATE TABLE `config_sections` (
                                 `id` int(11) NOT NULL AUTO_INCREMENT,
                                 `section_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Název sekce',
                                 PRIMARY KEY (`id`),
                                 KEY `section_name` (`section_name`(190))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sekce konfigurace';


DROP TABLE IF EXISTS `csrf_token`;
CREATE TABLE `csrf_token` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `session_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
                            `token` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
                            `created` datetime NOT NULL,
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `external_session`;
CREATE TABLE `external_session` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `session_id` varchar(128) NOT NULL,
                                  `external_session_id` varchar(255) NOT NULL,
                                  `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
                                  PRIMARY KEY (`id`),
                                  UNIQUE KEY `session_id` (`session_id`),
                                  KEY `external_session_id` (`external_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;


DROP VIEW IF EXISTS `inst_configs`;
CREATE TABLE `inst_configs` (`id` int(11), `source_id` int(11), `key_id` int(11), `value` mediumtext, `timestamp` timestamp);


DROP VIEW IF EXISTS `inst_keys`;
CREATE TABLE `inst_keys` (`id` int(11), `key_name` varchar(191), `section_id` int(11));


DROP VIEW IF EXISTS `inst_sections`;
CREATE TABLE `inst_sections` (`id` int(11), `section_name` varchar(191));


DROP VIEW IF EXISTS `inst_sources`;
CREATE TABLE `inst_sources` (`id` int(11), `library_name` varchar(191), `source` varchar(191), `driver` varchar(191));


DROP TABLE IF EXISTS `oai_resumption`;
CREATE TABLE `oai_resumption` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `params` text DEFAULT NULL,
                                `expires` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
                                PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP VIEW IF EXISTS `record`;
CREATE TABLE `record` (`id` int(11), `record_id` varchar(255), `source` varchar(50), `version` varchar(20), `data` longtext, `updated` datetime);


DROP VIEW IF EXISTS `resource`;
CREATE TABLE `resource` (`id` int(11), `record_id` varchar(255), `title` varchar(255), `author` varchar(255), `year` mediumint(6), `source` varchar(50), `extra_metadata` longtext);


DROP VIEW IF EXISTS `resource_tags`;
CREATE TABLE `resource_tags` (`id` int(11), `resource_id` int(11), `tag_id` int(11), `list_id` int(11), `user_id` int(11), `posted` timestamp);


DROP TABLE IF EXISTS `search`;
CREATE TABLE `search` (
                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL DEFAULT 0,
                        `session_id` varchar(128) DEFAULT NULL,
                        `folder_id` int(11) DEFAULT NULL,
                        `created` date NOT NULL DEFAULT '2000-01-01',
                        `title` varchar(20) DEFAULT NULL,
                        `saved` int(1) NOT NULL DEFAULT 0,
                        `search_object` blob DEFAULT NULL,
                        `checksum` int(11) DEFAULT NULL,
                        `notification_frequency` int(11) NOT NULL DEFAULT 0,
                        `last_notification_sent` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
                        `notification_base_url` varchar(255) NOT NULL DEFAULT '',
                        PRIMARY KEY (`id`),
                        KEY `user_id` (`user_id`),
                        KEY `folder_id` (`folder_id`),
                        KEY `session_id` (`session_id`),
                        KEY `notification_frequency_idx` (`notification_frequency`),
                        KEY `notification_base_url_idx` (`notification_base_url`),
                        KEY `created_saved` (`created`,`saved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                         `session_id` varchar(128) DEFAULT NULL,
                         `data` mediumtext DEFAULT NULL,
                         `last_used` int(12) NOT NULL DEFAULT 0,
                         `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `session_id` (`session_id`),
                         KEY `last_used` (`last_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `shortlinks`;
CREATE TABLE `shortlinks` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `path` mediumtext NOT NULL,
                            `hash` varchar(32) DEFAULT NULL,
                            `created` timestamp NOT NULL DEFAULT current_timestamp(),
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `shortlinks_hash_IDX` (`hash`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;


DROP TABLE IF EXISTS `system`;
CREATE TABLE `system` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `key` varchar(32) NOT NULL,
                        `value` varchar(32) NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `key_2` (`key`),
                        UNIQUE KEY `id` (`id`),
                        KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP VIEW IF EXISTS `tags`;
CREATE TABLE `tags` (`id` int(11), `tag` varchar(64));


DROP VIEW IF EXISTS `user`;
CREATE TABLE `user` (`id` int(11), `username` varchar(255), `password` varchar(32), `pass_hash` varchar(60), `firstname` varchar(50), `lastname` varchar(50), `email` varchar(255), `email_verified` datetime, `cat_id` varchar(255), `cat_username` varchar(50), `cat_password` varchar(70), `cat_pass_enc` varchar(255), `college` varchar(100), `major` varchar(100), `home_library` varchar(100), `created` datetime, `verify_hash` varchar(42), `last_login` timestamp, `auth_method` varchar(50), `pending_email` varchar(255), `user_provided_email` tinyint(1), `last_language` varchar(30));


DROP VIEW IF EXISTS `user_card`;
CREATE TABLE `user_card` (`id` int(11), `user_id` int(11), `card_name` varchar(255), `cat_username` varchar(50), `cat_password` varchar(70), `cat_pass_enc` varchar(255), `home_library` varchar(100), `created` datetime, `saved` timestamp, `eppn` varchar(64), `major` varchar(100), `edu_person_unique_id` varchar(255));


DROP VIEW IF EXISTS `user_list`;
CREATE TABLE `user_list` (`id` int(11), `user_id` int(11), `title` varchar(200), `description` mediumtext, `created` datetime, `public` int(11));


DROP VIEW IF EXISTS `user_resource`;
CREATE TABLE `user_resource` (`id` int(11), `user_id` int(11), `resource_id` int(11), `list_id` int(11), `notes` mediumtext, `saved` timestamp);


DROP VIEW IF EXISTS `user_settings`;
CREATE TABLE `user_settings` (`id` int(11), `user_id` int(11), `citation_style` int(8), `records_per_page` tinyint(4), `sorting` varchar(40), `saved_institutions` mediumtext);


DROP TABLE IF EXISTS `widget`;
CREATE TABLE `widget` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `name` varchar(40) NOT NULL,
                        `display` varchar(32) DEFAULT NULL,
                        `title_cs` varchar(128) NOT NULL,
                        `title_en` varchar(128) NOT NULL,
                        `show_all_records_link` tinyint(1) DEFAULT NULL,
                        `shown_records_number` int(11) DEFAULT NULL,
                        `show_cover` tinyint(1) DEFAULT NULL,
                        `description` varchar(32) DEFAULT NULL,
                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `widget_content`;
CREATE TABLE `widget_content` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `widget_id` int(11) NOT NULL,
                                `value` varchar(64) NOT NULL,
                                `preferred_value` tinyint(1) NOT NULL,
                                `description_cs` text DEFAULT NULL,
                                `description_en` text DEFAULT NULL,
                                PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `comments`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `comments` AS select `vufind6`.`comments`.`id` AS `id`,`vufind6`.`comments`.`user_id` AS `user_id`,`vufind6`.`comments`.`resource_id` AS `resource_id`,`vufind6`.`comments`.`comment` AS `comment`,`vufind6`.`comments`.`created` AS `created` from `vufind6`.`comments`;

DROP TABLE IF EXISTS `inst_configs`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `inst_configs` AS select `vufind6`.`inst_configs`.`id` AS `id`,`vufind6`.`inst_configs`.`source_id` AS `source_id`,`vufind6`.`inst_configs`.`key_id` AS `key_id`,`vufind6`.`inst_configs`.`value` AS `value`,`vufind6`.`inst_configs`.`timestamp` AS `timestamp` from `vufind6`.`inst_configs`;

DROP TABLE IF EXISTS `inst_keys`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `inst_keys` AS select `vufind6`.`inst_keys`.`id` AS `id`,`vufind6`.`inst_keys`.`key_name` AS `key_name`,`vufind6`.`inst_keys`.`section_id` AS `section_id` from `vufind6`.`inst_keys`;

DROP TABLE IF EXISTS `inst_sections`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `inst_sections` AS select `vufind6`.`inst_sections`.`id` AS `id`,`vufind6`.`inst_sections`.`section_name` AS `section_name` from `vufind6`.`inst_sections`;

DROP TABLE IF EXISTS `inst_sources`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `inst_sources` AS select `vufind6`.`inst_sources`.`id` AS `id`,`vufind6`.`inst_sources`.`library_name` AS `library_name`,`vufind6`.`inst_sources`.`source` AS `source`,`vufind6`.`inst_sources`.`driver` AS `driver` from `vufind6`.`inst_sources`;

DROP TABLE IF EXISTS `record`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `record` AS select `vufind6`.`record`.`id` AS `id`,`vufind6`.`record`.`record_id` AS `record_id`,`vufind6`.`record`.`source` AS `source`,`vufind6`.`record`.`version` AS `version`,`vufind6`.`record`.`data` AS `data`,`vufind6`.`record`.`updated` AS `updated` from `vufind6`.`record`;

DROP TABLE IF EXISTS `resource`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `resource` AS select `vufind6`.`resource`.`id` AS `id`,`vufind6`.`resource`.`record_id` AS `record_id`,`vufind6`.`resource`.`title` AS `title`,`vufind6`.`resource`.`author` AS `author`,`vufind6`.`resource`.`year` AS `year`,`vufind6`.`resource`.`source` AS `source`,`vufind6`.`resource`.`extra_metadata` AS `extra_metadata` from `vufind6`.`resource`;

DROP TABLE IF EXISTS `resource_tags`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `resource_tags` AS select `vufind6`.`resource_tags`.`id` AS `id`,`vufind6`.`resource_tags`.`resource_id` AS `resource_id`,`vufind6`.`resource_tags`.`tag_id` AS `tag_id`,`vufind6`.`resource_tags`.`list_id` AS `list_id`,`vufind6`.`resource_tags`.`user_id` AS `user_id`,`vufind6`.`resource_tags`.`posted` AS `posted` from `vufind6`.`resource_tags`;

DROP TABLE IF EXISTS `tags`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `tags` AS select `vufind6`.`tags`.`id` AS `id`,`vufind6`.`tags`.`tag` AS `tag` from `vufind6`.`tags`;

DROP TABLE IF EXISTS `user`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `user` AS select `vufind6`.`user`.`id` AS `id`,`vufind6`.`user`.`username` AS `username`,`vufind6`.`user`.`password` AS `password`,`vufind6`.`user`.`pass_hash` AS `pass_hash`,`vufind6`.`user`.`firstname` AS `firstname`,`vufind6`.`user`.`lastname` AS `lastname`,`vufind6`.`user`.`email` AS `email`,`vufind6`.`user`.`email_verified` AS `email_verified`,`vufind6`.`user`.`cat_id` AS `cat_id`,`vufind6`.`user`.`cat_username` AS `cat_username`,`vufind6`.`user`.`cat_password` AS `cat_password`,`vufind6`.`user`.`cat_pass_enc` AS `cat_pass_enc`,`vufind6`.`user`.`college` AS `college`,`vufind6`.`user`.`major` AS `major`,`vufind6`.`user`.`home_library` AS `home_library`,`vufind6`.`user`.`created` AS `created`,`vufind6`.`user`.`verify_hash` AS `verify_hash`,`vufind6`.`user`.`last_login` AS `last_login`,`vufind6`.`user`.`auth_method` AS `auth_method`,`vufind6`.`user`.`pending_email` AS `pending_email`,`vufind6`.`user`.`user_provided_email` AS `user_provided_email`,`vufind6`.`user`.`last_language` AS `last_language` from `vufind6`.`user`;

DROP TABLE IF EXISTS `user_card`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `user_card` AS select `vufind6`.`user_card`.`id` AS `id`,`vufind6`.`user_card`.`user_id` AS `user_id`,`vufind6`.`user_card`.`card_name` AS `card_name`,`vufind6`.`user_card`.`cat_username` AS `cat_username`,`vufind6`.`user_card`.`cat_password` AS `cat_password`,`vufind6`.`user_card`.`cat_pass_enc` AS `cat_pass_enc`,`vufind6`.`user_card`.`home_library` AS `home_library`,`vufind6`.`user_card`.`created` AS `created`,`vufind6`.`user_card`.`saved` AS `saved`,`vufind6`.`user_card`.`eppn` AS `eppn`,`vufind6`.`user_card`.`major` AS `major`,`vufind6`.`user_card`.`edu_person_unique_id` AS `edu_person_unique_id` from `vufind6`.`user_card`;

DROP TABLE IF EXISTS `user_list`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `user_list` AS select `vufind6`.`user_list`.`id` AS `id`,`vufind6`.`user_list`.`user_id` AS `user_id`,`vufind6`.`user_list`.`title` AS `title`,`vufind6`.`user_list`.`description` AS `description`,`vufind6`.`user_list`.`created` AS `created`,`vufind6`.`user_list`.`public` AS `public` from `vufind6`.`user_list`;

DROP TABLE IF EXISTS `user_resource`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `user_resource` AS select `vufind6`.`user_resource`.`id` AS `id`,`vufind6`.`user_resource`.`user_id` AS `user_id`,`vufind6`.`user_resource`.`resource_id` AS `resource_id`,`vufind6`.`user_resource`.`list_id` AS `list_id`,`vufind6`.`user_resource`.`notes` AS `notes`,`vufind6`.`user_resource`.`saved` AS `saved` from `vufind6`.`user_resource`;

DROP TABLE IF EXISTS `user_settings`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `user_settings` AS select `vufind6`.`user_settings`.`id` AS `id`,`vufind6`.`user_settings`.`user_id` AS `user_id`,`vufind6`.`user_settings`.`citation_style` AS `citation_style`,`vufind6`.`user_settings`.`records_per_page` AS `records_per_page`,`vufind6`.`user_settings`.`sorting` AS `sorting`,`vufind6`.`user_settings`.`saved_institutions` AS `saved_institutions` from `vufind6`.`user_settings`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `record_status` AS
SELECT
`vufind`.`record_status`.`record_id` AS `record_id`,
`vufind`.`record_status`.`absent_total` AS `absent_total`,
`vufind`.`record_status`.`absent_on_loan` AS `absent_on_loan`,
`vufind`.`record_status`.`present_total` AS `present_total`,
`vufind`.`record_status`.`present_on_loan` AS `present_on_loan`,
`vufind`.`record_status`.`last_update` AS `last_update`
FROM `vufind`.`record_status`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `import_record_status_loans` AS
SELECT
`vufind`.`import_record_status_loans`.`record_id` AS `record_id`,
`vufind`.`import_record_status_loans`.`source` AS `source`,
`vufind`.`import_record_status_loans`.`absent_on_loan` AS `absent_on_loan`,
`vufind`.`import_record_status_loans`.`present_on_loan` AS `present_on_loan`
FROM `vufind`.`import_record_status_loans`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `import_record_status_totals` AS
SELECT
`vufind`.`import_record_status_totals`.`record_id` AS `record_id`,
`vufind`.`import_record_status_totals`.`source` AS `source`,
`vufind`.`import_record_status_totals`.`absent_total` AS `absent_total`,
`vufind`.`import_record_status_totals`.`present_total` AS `present_total`
FROM `vufind`.`import_record_status_totals`;

SET foreign_key_checks = 1;
-- 2022-07-19 09:48:52


-- Permissions
GRANT CREATE ROUTINE, CREATE TEMPORARY TABLES, LOCK TABLES, ALTER, CREATE, CREATE VIEW, DELETE, DROP, INDEX, INSERT,
      REFERENCES, SELECT, SHOW VIEW, TRIGGER, UPDATE, ALTER ROUTINE, EXECUTE
ON `vufind_[view]`.* TO 'vufind6'@'%';

