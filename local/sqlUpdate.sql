# noinspection SqlNoDataSourceInspectionForFile

/* feature-1192 */
/* Make Knihovny.cz database structure compatible with original VuFind */
/* VuFind 2.5 */
ALTER TABLE `user`
  MODIFY COLUMN `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  MODIFY COLUMN `email` varchar(255) NOT NULL DEFAULT '';

/* VuFind 3.0 */
ALTER TABLE `user`
  MODIFY COLUMN `cat_password` varchar(70) DEFAULT NULL;

ALTER TABLE `resource`
  MODIFY COLUMN `record_id` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `resource`
  MODIFY COLUMN  `source` varchar(50) NOT NULL DEFAULT 'Solr';

UPDATE `resource` SET source='Solr' WHERE source='VuFind';

ALTER TABLE `resource`
  MODIFY COLUMN `title` varchar(255) NOT NULL DEFAULT '',
  MODIFY COLUMN `author` varchar(255) DEFAULT NULL;

/* Modification needed for MySQL strict mode */
ALTER TABLE `comments`
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

ALTER TABLE `oai_resumption`
  MODIFY COLUMN `expires` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

ALTER TABLE `search`
  MODIFY COLUMN `created` date NOT NULL DEFAULT '2000-01-01';

ALTER TABLE `search`
  ADD COLUMN `checksum` int(11) DEFAULT NULL AFTER search_object;

ALTER TABLE `session`
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

ALTER TABLE `user`
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

ALTER TABLE `user_card`
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

ALTER TABLE `user_list`
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00';

/* VuFind 3.1 */
ALTER TABLE `tags` CONVERT TO CHARACTER SET utf8 COLLATE utf8_bin;

/* VuFind 4.0 */
CREATE TABLE `external_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(128) NOT NULL,
  `external_session_id` varchar(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `external_session_id` (`external_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin;

ALTER TABLE `user`
  ADD COLUMN `cat_id` varchar(255) DEFAULT NULL AFTER email,
  ADD UNIQUE KEY `cat_id` (`cat_id`);

/* VuFind 5.0 */
ALTER TABLE `user`
  ADD COLUMN auth_method varchar(50) DEFAULT NULL AFTER last_login;

ALTER TABLE `session`
  MODIFY COLUMN `data` mediumtext;

/* VuFind 5.1 */
ALTER TABLE `resource`
  ADD COLUMN `extra_metadata` mediumtext DEFAULT NULL AFTER source;

ALTER TABLE `user`
  MODIFY COLUMN `cat_pass_enc` varchar(255) DEFAULT NULL;

ALTER TABLE `user_card`
  MODIFY COLUMN `cat_password` varchar(70) DEFAULT NULL,
  MODIFY COLUMN `cat_pass_enc` varchar(255) DEFAULT NULL;

/* VuFind 6.0 */
ALTER TABLE `user`
  ADD COLUMN `email_verified` datetime DEFAULT NULL AFTER email;

CREATE TABLE `shortlinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` mediumtext NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin;

UPDATE `system` SET `value` = '65' WHERE `key`='DB_VERSION';


/* VuFind 6.1 */
--
-- Modifications to table `user`
--

ALTER TABLE `user`
  ADD COLUMN pending_email varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `user`
  ADD COLUMN user_provided_email tinyint(1) NOT NULL DEFAULT '0';

ALTER TABLE `user`
  ADD COLUMN last_language varchar(30) NOT NULL DEFAULT '';

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

UPDATE `system` SET `value` = '100' WHERE `key`='DB_VERSION';

--
-- Add foreign key to user card
--
DELETE FROM `user_card` WHERE user_id NOT IN (SELECT id FROM user);
ALTER TABLE `user_card`
    ADD CONSTRAINT `user_card_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
UPDATE `system` SET `value` = '101' WHERE `key`='DB_VERSION';

--
-- Create table config_files
--
CREATE TABLE `config_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `file_name` varchar(191) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Název souboru',
    PRIMARY KEY (`id`),
    KEY `name` (`file_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Konfigurační soubory';

INSERT INTO `config_files` (`id`, `file_name`) VALUES
(2,	'content'),
(1,	'searches');

--
-- Create table config_sections
--

DROP TABLE IF EXISTS `config_sections`;
CREATE TABLE `config_sections` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `section_name` varchar(191) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Název sekce',
    PRIMARY KEY (`id`),
    KEY `section_name` (`section_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Sekce konfigurace';

INSERT INTO `config_sections` (`id`, `section_name`) VALUES
(3,	'DocumentTypesContentBlock'),
(1,	'HomePage'),
(2,	'Inspiration');

--
-- Create table config_items
--

DROP TABLE IF EXISTS `config_items`;
CREATE TABLE `config_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(191) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Název položky',
    `type` enum('string','array') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'string' COMMENT 'Typ',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Konfigurační položky';

INSERT INTO `config_items` (`id`, `name`, `type`) VALUES
(1,	'content',	'array'),
(2,	'item',	'array'),
(3,	'content_block',	'array');

--
-- Create table config
--
DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `file_id` int(11) NOT NULL COMMENT 'Soubor',
    `section_id` int(11) NOT NULL COMMENT 'Sekce',
    `item_id` int(11) NOT NULL COMMENT 'Položka (klíč)',
    `array_key` varchar(191) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Klíč pole (nepovinné)',
    `value` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Hodnota',
    `order` int(11) NOT NULL COMMENT 'Pořadí',
    `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Aktivní?',
    PRIMARY KEY (`id`),
    KEY `file_id` (`file_id`),
    KEY `section_id` (`section_id`),
    KEY `active` (`active`),
    KEY `item_id` (`item_id`),
    CONSTRAINT `config_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `config_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `config_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `config_files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `config_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `config_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Konfigurace';

INSERT INTO `config` (`id`, `file_id`, `section_id`, `item_id`, `array_key`, `value`, `order`, `active`) VALUES
(1,	2,	2,	3,	NULL,	'Inspiration:ceny_nebula_a_hugo',	0,	1),
(2,	2,	2,	3,	NULL,	'Inspiration:templars',	0,	1),
(3,	1,	1,	1,	NULL,	'TemplateBased:header-panel',	10,	1),
(4,	1,	1,	1,	NULL,	'DocumentTypes:DocumentTypesContentBlock',	20,	1),
(5,	2,	2,	3,	NULL,	'TemplateBased:header-panel',	0,	1),
(6,	1,	1,	1,	NULL,	'Inspiration:eknihy_ke_stazeni',	30,	1),
(7,	1,	1,	1,	NULL,	'UserList:162',	40,	1),
(8,	1,	3,	2,	NULL,	'doctypes_widget_norms;doctypes_widget_norms_description;pr-format-norms;0/NORMS/',	10,	1),
(9,	1,	3,	2,	NULL,	'doctypes_widget_maps;doctypes_widget_maps_description;pr-format-maps;0/MAPS/',	20,	1),
(10,	1,	3,	2,	NULL,	'doctypes_widget_legislative_laws;doctypes_widget_legislative_laws_description;pr-format-legislative;0/LEGISLATIVE/',	30,	1),
(11,	1,	3,	2,	NULL,	'doctypes_widget_authorities;doctypes_widget_authorities_description;pr-format-otherperson;1/OTHER/PERSON/',	40,	1),
(12,	1,	3,	2,	NULL,	'doctypes_widget_patents;doctypes_widget_patents_description;pr-format-patents;0/PATENTS/',	50,	1),
(13,	1,	3,	2,	NULL,	'doctypes_widget_articles;doctypes_widget_articles_description;pr-format-articles;0/ARTICLES/',	60,	1),
(14,	1,	3,	2,	NULL,	'doctypes_widget_musical_scores;doctypes_widget_musical_scores_description;pr-format-musicalscores;0/MUSICAL_SCORES/',	70,	1);

--
-- Komentáře k tabulce citation_styles
--
ALTER TABLE `citation_style`
    CHANGE `description` `description` varchar(32) COLLATE 'utf8_general_ci' NULL COMMENT 'Popis citačního stylu' AFTER `id`,
    CHANGE `value` `value` varchar(8) COLLATE 'utf8_general_ci' NULL COMMENT 'Id v Citace.com' AFTER `description`,
    COMMENT='Citační styly';

UPDATE `system` SET `value` = '102' WHERE `key`='DB_VERSION';

--
-- Odstranění nepoužívaných tabulek
--
DROP TABLE `frontend`;
DROP TABLE `infobox`;
DROP TABLE `inspirations`;
DROP TABLE `portal_pages`;

UPDATE `system` SET `value` = '69' WHERE `key`='DB_VERSION';

INSERT INTO `config_files` (`file_name`) VALUES ('citation');
INSERT INTO `config_sections` (`section_name`) VALUES ('Citation');
INSERT INTO `config_items` (`name`, `type`) VALUES ('default_citation_style', 1);
INSERT INTO `config_items` (`name`, `type`) VALUES ('citation_local_domain', 1);

INSERT INTO config (file_id, section_id, item_id, array_key, value, `order`, active)
VALUES (
           (SELECT id FROM config_files WHERE file_name = 'citation'),
           (SELECT id FROM config_sections WHERE section_name = 'Citation'),
           (SELECT id FROM config_items WHERE name = 'default_citation_style'),
           NULL, '38673', 0, 1);

INSERT INTO config (file_id, section_id, item_id, array_key, value, `order`, active)
VALUES (
           (SELECT id FROM config_files WHERE file_name = 'citation'),
           (SELECT id FROM config_sections WHERE section_name = 'Citation'),
           (SELECT id FROM config_items WHERE name = 'citation_local_domain'),
           NULL, 'cpk-front.mzk.cz', 0, 1);

UPDATE `system` SET `value` = '103' WHERE `key`='DB_VERSION';

/* Add table for record cache */
CREATE TABLE `record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` varchar(255) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `version` varchar(20) NOT NULL,
  `data` longtext DEFAULT NULL,
  `updated` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_id_source` (`record_id`, `source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE `system` SET `value` = '104' WHERE `key`='DB_VERSION';

/* Update table resource_tags */
ALTER TABLE `resource_tags` CHANGE COLUMN `resource_id` `resource_id` int(11) DEFAULT NULL;

UPDATE `system` SET `value` = '105' WHERE `key`='DB_VERSION';

/* Update table shortlinks */
ALTER TABLE `shortlinks` ADD COLUMN `hash` varchar(32) AFTER `path`;
ALTER TABLE `shortlinks` ADD UNIQUE KEY `shortlinks_hash_IDX` USING HASH (`hash`);

UPDATE `system` SET `value` = '106' WHERE `key`='DB_VERSION';

-- Issue 172: CitacePro
INSERT INTO `config_items` (`name`, `type`) VALUES ('citation_styles', 'array');
INSERT INTO `config` (`file_id`, `section_id`, `item_id`, `array_key`, `value`, `order`, `active`)
SELECT (SELECT `id` FROM `config_files` WHERE `file_name` = 'citation'),
       (SELECT `id` FROM `config_sections` WHERE `section_name` = 'Citation'),
       (SELECT `id` FROM `config_items` WHERE `name` = 'citation_styles'),
       `value`, `description`, `id`, 1
FROM `citation_style`;

UPDATE `system` SET `value` = '107' WHERE `key`='DB_VERSION';

UPDATE user SET username = SUBSTRING_INDEX(username, ';', 1);

UPDATE `system` SET `value` = '108' WHERE `key`='DB_VERSION';

-- Convert to utf8mb4
DROP TABLE email_delayer;
DROP TABLE email_types;
DROP TABLE libraries_geolocations;
DROP TABLE notifications;
DROP TABLE notification_types;
DROP TABLE user_stats;
DROP TABLE user_stats_fields;

ALTER TABLE `resource` DROP KEY `record_id`;
ALTER TABLE `resource` ADD KEY `record_id` (`record_id`(190));
ALTER TABLE `search` DROP KEY `notification_base_url_idx`;
ALTER TABLE `search` ADD KEY `notification_base_url` (`notification_base_url`(190));
ALTER TABLE `external_session` DROP KEY `external_session_id`;
ALTER TABLE `external_session` ADD KEY `external_session_id` (`external_session_id`(190));
ALTER TABLE `user` DROP KEY `username`;
ALTER TABLE `user` ADD UNIQUE KEY `username` (`username`(190));
ALTER TABLE `user` DROP KEY `cat_id`;
ALTER TABLE `user` ADD UNIQUE KEY `cat_id` (`cat_id`(190));
ALTER TABLE `record` DROP KEY `record_id_source`;
ALTER TABLE `record` ADD UNIQUE KEY `record_id_source` (`record_id`(140), `source`);
ALTER TABLE `auth_hash` DROP KEY `hash_type`;
ALTER TABLE `auth_hash` ADD UNIQUE KEY `hash_type` (`hash`(140), `type`);
ALTER TABLE `config_files` DROP KEY `name`;
ALTER TABLE `config_files` ADD KEY `name` (`file_name`(190));
ALTER TABLE `config_sections` DROP KEY `section_name`;
ALTER TABLE `config_sections` ADD KEY `section_name` (`section_name`(190));
ALTER TABLE `inst_keys` DROP KEY `key_name_section_id`;
ALTER TABLE `inst_keys` ADD UNIQUE KEY `key_name_section_id` (`key_name`(180), `section_id`);
ALTER TABLE `inst_sections` DROP KEY `section_name`;
ALTER TABLE `inst_sections` ADD KEY `section_name` (`section_name`(190));
ALTER TABLE `inst_sources` DROP KEY `source`;
ALTER TABLE `inst_sources` ADD UNIQUE KEY `source` (`source`(190));
ALTER TABLE `inst_sources` DROP KEY `driver`;
ALTER TABLE `inst_sources` ADD KEY `driver` (`driver`(190));

ALTER TABLE `auth_hash` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `change_tracker` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `citation_style` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `comments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `config` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `config_files` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `config_items` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `config_sections` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `external_session` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
ALTER TABLE `inst_configs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `inst_keys` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `inst_sections` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `inst_sources` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `login` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `modal_specific_contents` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `oai_resumption` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `record` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `resource` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `resource_tags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `search` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `session` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `shortlinks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
ALTER TABLE `system` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
ALTER TABLE `user` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_card` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_list` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_resource` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_settings` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `widget` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `widget_content` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

UPDATE `system` SET `value` = '109' WHERE `key`='DB_VERSION';

-- Issue 211 Identities
-- eppn is replaced by edu_person_unique_id
ALTER TABLE user_card DROP KEY user_card_eppn_uq;
ALTER TABLE user_card ADD COLUMN edu_person_unique_id VARCHAR(255) DEFAULT NULL;
-- migration for IdPs with Aleph and Koha - edu_person_unique_id is different from eppn
UPDATE user_card
SET edu_person_unique_id = CONCAT(SUBSTR(cat_username, POSITION('.' IN cat_username) + 1), SUBSTR(eppn, POSITION('@' IN eppn)))
WHERE cat_username LIKE 'kkpc.%'
  OR cat_username LIKE 'knav.%'
  OR cat_username LIKE 'nkp.%'
  OR cat_username LIKE 'ntk.%'
  OR cat_username LIKE 'svkhk.%'
  OR cat_username LIKE 'svkos.%'
  OR cat_username LIKE 'svkpk.%'
  OR cat_username LIKE 'uzei.%'
  OR cat_username LIKE 'vkol.%'
  OR cat_username LIKE 'cvgz.%'
  OR cat_username LIKE 'tre.%'
  OR cat_username LIKE 'vkta.%'
  OR cat_username LIKE 'mkuo.%';
-- migration for other IdPs - attribute edu_person_unique_id is same as eppn
UPDATE user_card SET edu_person_unique_id = eppn WHERE edu_person_unique_id IS NULL;
CREATE UNIQUE INDEX user_card_edu_person_unique_id_uq ON user_card(edu_person_unique_id);

UPDATE `system` SET `value` = '110' WHERE `key`='DB_VERSION';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
UPDATE `system` SET `value` = '111' WHERE `key`='DB_VERSION';
