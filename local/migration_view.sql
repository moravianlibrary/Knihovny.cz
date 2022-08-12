#!!! apply file 01_config.sql first

-- feature-1192
-- Make Knihovny.cz database structure compatible with original VuFind

-- Modification needed for MySQL strict mode
ALTER TABLE `oai_resumption`
  MODIFY COLUMN `expires` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

ALTER TABLE `search`
  MODIFY COLUMN `created` date NOT NULL DEFAULT '2000-01-01';

ALTER TABLE `search`
  ADD COLUMN `checksum` int(11) DEFAULT NULL AFTER search_object;

ALTER TABLE `session`
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

-- VuFind 4.0
CREATE TABLE `external_session` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `session_id` varchar(128) NOT NULL,
    `external_session_id` varchar(255) NOT NULL,
    `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
    PRIMARY KEY (`id`),
    UNIQUE KEY `session_id` (`session_id`),
    KEY `external_session_id` (`external_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin;

-- VuFind 5.0
ALTER TABLE `session`
  MODIFY COLUMN `data` mediumtext;

-- VuFind 6.0

CREATE TABLE `shortlinks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `path` mediumtext NOT NULL,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin;

--
-- Modifications to table `search`
--
ALTER TABLE `search`
  MODIFY COLUMN id bigint unsigned NOT NULL AUTO_INCREMENT;

--
-- Modifications to table `session`
--
ALTER TABLE `session`
  MODIFY COLUMN id bigint unsigned NOT NULL AUTO_INCREMENT;

--
-- Modifications to table `external_session`
--
ALTER TABLE `external_session`
  MODIFY COLUMN id bigint unsigned NOT NULL AUTO_INCREMENT;

--
-- Modifications to table `search`
--
ALTER TABLE `search`
  ADD COLUMN notification_frequency int(11) NOT NULL DEFAULT '0',
  ADD COLUMN last_notification_sent datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  ADD COLUMN notification_base_url varchar(255) NOT NULL DEFAULT '';

CREATE INDEX notification_frequency_idx ON search (notification_frequency);
CREATE INDEX notification_base_url_idx ON search (notification_base_url);

--
-- Table structure for table auth_hash
--
CREATE TABLE `auth_hash` (
   `id` bigint unsigned NOT NULL AUTO_INCREMENT,
   `session_id` varchar(128) DEFAULT NULL,
   `hash` varchar(255) NOT NULL DEFAULT '',
   `type` varchar(50) DEFAULT NULL,
   `data` mediumtext,
   `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   KEY `session_id` (`session_id`),
   UNIQUE KEY `hash_type` (`hash`, `type`),
   KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Update table shortlinks (VuFind 7.0)
ALTER TABLE `shortlinks` ADD COLUMN `hash` varchar(32) AFTER `path`;
ALTER TABLE `shortlinks` ADD UNIQUE KEY `shortlinks_hash_IDX` USING HASH (`hash`);

--
-- Odstranění nepoužívaných tabulek
--
DROP TABLE IF EXISTS `frontend`;
DROP TABLE IF EXISTS `infobox`;
DROP TABLE IF EXISTS `inspirations`;
DROP TABLE IF EXISTS `portal_pages`;
DROP TABLE IF EXISTS `citation_style`;
DROP TABLE IF EXISTS `hosted_idps`;
DROP TABLE IF EXISTS `email_delayer`;
DROP TABLE IF EXISTS `email_types`;
DROP TABLE IF EXISTS `libraries_geolocations`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `notification_types`;
DROP TABLE IF EXISTS `user_stats`;
DROP TABLE IF EXISTS `user_stats_fields`;

--
-- Vytvoření tabulky pro CSRF tokeny
--
DROP TABLE IF EXISTS `csrf_token`;
CREATE TABLE `csrf_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(128) NOT NULL,
  `token` varchar(128) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

DROP VIEW `libraries_geolocations`;
DROP VIEW `inst_configs`;

CREATE VIEW `inst_sources` AS SELECT * FROM `vufind`.`inst_sources`;
CREATE VIEW `inst_sections` AS SELECT * FROM `vufind`.`inst_sections`;
CREATE VIEW `inst_keys` AS SELECT * FROM `vufind`.`inst_keys`;
CREATE VIEW `inst_configs` AS SELECT * FROM `vufind`.`inst_configs`;

-- Convert to utf8mb4
ALTER TABLE `search` DROP KEY `notification_base_url_idx`;
ALTER TABLE `search` ADD KEY `notification_base_url` (`notification_base_url`(190));
ALTER TABLE `external_session` DROP KEY `external_session_id`;
ALTER TABLE `external_session` ADD KEY `external_session_id` (`external_session_id`(190));
ALTER TABLE `auth_hash` DROP KEY `hash_type`;
ALTER TABLE `auth_hash` ADD UNIQUE KEY `hash_type` (`hash`(140), `type`);
ALTER TABLE `auth_hash` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `change_tracker` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `external_session` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
ALTER TABLE `oai_resumption` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `search` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `session` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `shortlinks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
ALTER TABLE `system` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `widget` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `widget_content` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

UPDATE `config` SET `active` = 0;

-- User related tables should be views
DROP TABLE IF EXISTS `modal_specific_contents`;
DROP TABLE IF EXISTS `record`;
DROP TABLE IF EXISTS `user_settings`;
DROP TABLE IF EXISTS `citation_style`;
DROP TABLE IF EXISTS `resource_tags`;
DROP TABLE IF EXISTS `user_resource`;
DROP TABLE IF EXISTS `user_card`;
DROP TABLE IF EXISTS `user_list`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `resource`;
DROP TABLE IF EXISTS `tags`;
DROP TABLE IF EXISTS `user`;

CREATE VIEW `user` AS SELECT * FROM `vufind`.`user`;
CREATE VIEW `user_card` AS SELECT * FROM `vufind`.`user_card`;
CREATE VIEW `user_list` AS SELECT * FROM `vufind`.`user_list`;
CREATE VIEW `user_resource` AS SELECT * FROM `vufind`.`user_resource`;
CREATE VIEW `user_settings` AS SELECT * FROM `vufind`.`user_settings`;
CREATE VIEW `resource` AS SELECT * FROM `vufind`.`resource`;
CREATE VIEW `resource_tags` AS SELECT * FROM `vufind`.`resource_tags`;
CREATE VIEW `tags` AS SELECT * FROM `vufind`.`tags`;
CREATE VIEW `record` AS SELECT * FROM `vufind`.`record`;
CREATE VIEW `comments` AS SELECT * FROM `vufind`.`comments`;
CREATE VIEW `feedback` AS SELECT * FROM `vufind`.`feedback`;

DELETE FROM `system` WHERE `key` != 'DB_VERSION';
UPDATE `system` SET `value` = '100' WHERE `key` = 'DB_VERSION';
