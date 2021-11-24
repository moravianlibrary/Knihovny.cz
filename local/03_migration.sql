-- feature-1192
-- Make Knihovny.cz database structure compatible with original VuFind
-- VuFind 2.5
ALTER TABLE `user`
  MODIFY COLUMN `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  MODIFY COLUMN `email` varchar(255) NOT NULL DEFAULT '';

-- VuFind 3.0
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

-- Modification needed for MySQL strict mode
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

-- VuFind 3.1
ALTER TABLE `tags` CONVERT TO CHARACTER SET utf8 COLLATE utf8_bin;

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

ALTER TABLE `user`
  ADD COLUMN `cat_id` varchar(255) DEFAULT NULL AFTER email,
  ADD UNIQUE KEY `cat_id` (`cat_id`);

-- VuFind 5.0
ALTER TABLE `user`
  ADD COLUMN auth_method varchar(50) DEFAULT NULL AFTER last_login;

ALTER TABLE `session`
  MODIFY COLUMN `data` mediumtext;

-- VuFind 5.1
ALTER TABLE `resource`
  ADD COLUMN `extra_metadata` mediumtext DEFAULT NULL AFTER source;

ALTER TABLE `user`
  MODIFY COLUMN `cat_pass_enc` varchar(255) DEFAULT NULL;

ALTER TABLE `user_card`
  MODIFY COLUMN `cat_password` varchar(70) DEFAULT NULL,
  MODIFY COLUMN `cat_pass_enc` varchar(255) DEFAULT NULL;

-- VuFind 6.0
ALTER TABLE `user`
  ADD COLUMN `email_verified` datetime DEFAULT NULL AFTER email;

CREATE TABLE `shortlinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` mediumtext NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin;

-- VuFind 6.1
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

--
-- Add foreign key to user card
--
DELETE FROM `user_card` WHERE user_id NOT IN (SELECT id FROM user);
ALTER TABLE `user_card`
    ADD CONSTRAINT `user_card_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Odstranění nepoužívaných tabulek
--
DROP TABLE `frontend`;
DROP TABLE `infobox`;
DROP TABLE `inspirations`;
DROP TABLE `portal_pages`;
DROP TABLE `citation_style`;

-- Add table for record cache
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

-- Update table resource_tags (VuFind 7.1)
ALTER TABLE `resource_tags` CHANGE COLUMN `resource_id` `resource_id` int(11) DEFAULT NULL;

-- Update table shortlinks (VuFind 7.0)
ALTER TABLE `shortlinks` ADD COLUMN `hash` varchar(32) AFTER `path`;
ALTER TABLE `shortlinks` ADD UNIQUE KEY `shortlinks_hash_IDX` USING HASH (`hash`);

-- Migrate usernames
UPDATE user SET username = SUBSTRING_INDEX(username, ';', 1);

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
ALTER TABLE `comments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `external_session` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
ALTER TABLE `inst_configs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `inst_keys` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `inst_sections` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `inst_sources` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
CREATE UNIQUE INDEX user_card_edu_person_unique_id_uq ON user_card(edu_person_unique_id(190));

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
