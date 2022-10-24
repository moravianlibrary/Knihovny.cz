-- Add SQL update scripts here. Don't forget to add revision number after each script. Example below:
-- UPDATE `system` SET `value` = '101' WHERE `key`='DB_VERSION';

-- Issue 501
DELETE FROM `config_items` WHERE (`name` = 'citation_local_domain');
UPDATE `system` SET `value` = '101' WHERE `key`='DB_VERSION';

-- Issue 503
ALTER TABLE `widget`
  CHANGE `id` `id` int(11) NOT NULL COMMENT 'Id' AUTO_INCREMENT FIRST,
  CHANGE `name` `name` varchar(40) COLLATE 'utf8mb4_unicode_ci' NOT NULL COMMENT 'Jméno pro konfiguraci' AFTER `id`,
  CHANGE `title_cs` `title_cs` varchar(128) COLLATE 'utf8mb4_unicode_ci' NOT NULL COMMENT 'Český název' AFTER `display`,
  CHANGE `title_en` `title_en` varchar(128) COLLATE 'utf8mb4_unicode_ci' NOT NULL COMMENT 'Anglický název' AFTER `title_cs`,
  COMMENT='Inspirační seznamy';

ALTER TABLE `widget_content`
  CHANGE `widget_id` `widget_id` int(11) NOT NULL COMMENT 'Inspirační seznam' AFTER `id`,
  CHANGE `value` `value` varchar(64) COLLATE 'utf8mb4_unicode_ci' NOT NULL COMMENT 'Id záznamu' AFTER `widget_id`,
  COMMENT='Inspirační seznamy - obsah';

ALTER TABLE `widget_content`
DROP `preferred_value`,
DROP `description_cs`,
DROP `description_en`;

ALTER TABLE `widget`
DROP `display`,
DROP `show_all_records_link`,
DROP `shown_records_number`,
DROP `show_cover`,
DROP `description`;

DELETE FROM widget_content WHERE widget_id NOT IN (SELECT id FROM widget);
ALTER TABLE `widget_content` ADD INDEX `widget_id` (`widget_id`);
ALTER TABLE `widget_content` ADD FOREIGN KEY (`widget_id`) REFERENCES `widget` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE `system` SET `value` = '102' WHERE `key`='DB_VERSION';

-- Issue 517: ILS driver configuration cleanup
ALTER TABLE `inst_keys`
  ADD FOREIGN KEY (`section_id`) REFERENCES `inst_sections` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Timeout settings
DELETE `inst_configs`
FROM `inst_configs`
  JOIN `inst_sources` ON `inst_configs`.`source_id` = `inst_sources`.`id`
  JOIN `inst_keys` ON `inst_configs`.`key_id` = `inst_keys`.`id`
WHERE `inst_sources`.`driver` != 'ncip'
  AND `inst_keys`.`key_name` = 'timeout';

UPDATE `inst_keys` SET `key_name` = 'http_timeout' WHERE `key_name` = 'timeout';

-- Payment url configuration fix
UPDATE `inst_configs`
  JOIN `inst_keys` ON `inst_configs`.`key_id` = `inst_keys`.`id`
  SET `inst_configs`.`key_id` = (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'paymentUrl')
WHERE `inst_keys`.`key_name` = 'payment_url';

DELETE FROM `inst_keys` WHERE (`key_name` = 'payment_url');

-- Remove obsolete configuration keys
DELETE FROM `inst_keys` WHERE (`key_name` = 'hmac_key');
DELETE FROM `inst_keys` WHERE (`key_name` = 'hasUntrustedSSL');
DELETE FROM `inst_keys` WHERE (`key_name` = 'maximumItemsCount');
DELETE FROM `inst_keys` WHERE (`key_name` = 'hideHoldLinks');
DELETE FROM `inst_keys` WHERE (`key_name` = 'pick_up_location');
DELETE FROM `inst_keys` WHERE (`key_name` = 'relative_path');
DELETE FROM `inst_keys` WHERE (`key_name` = 'send_language');
DELETE FROM `inst_keys` WHERE (`key_name` = 'prolong_registration_status');
DELETE FROM `inst_keys` WHERE (`key_name` = 'maxItemsParsed');

-- Add httpBasicAuth configuration to NCIP template
INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`) VALUES
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'httpBasicAuth'), 0);

-- Add tokenBasicAuth configuration to NCIP template
INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`) VALUES
  ((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'tokenBasicAuth'), 0);

-- Add clientId configuration to NCIP template
INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`) VALUES
  ((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'clientId'), '');

-- Add clientSecret configuration to NCIP template
INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`) VALUES
  ((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'clientSecret'), '');

-- Add tokenEndpoint configuration to NCIP template
INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`) VALUES
  ((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'tokenEndpoint'), '');

-- Remove unneeded values from configurations
DELETE `inst_configs`
  FROM `inst_configs`
  JOIN `inst_keys` ON `inst_configs`.`key_id` = `inst_keys`.`id`
WHERE `inst_keys`.`key_name` IN ('HMACKeys', 'extraHoldFields');

-- Fix default patron setting name
UPDATE `inst_keys` SET `key_name` = 'default_patron_id' WHERE `key_name` = 'default_patron';

-- Remove agency setting from Koha libraries
DELETE `inst_configs`
  FROM `inst_configs`
  JOIN `inst_sources` ON `inst_sources`.`id` = `inst_configs`.`source_id`
  JOIN `inst_keys` ON `inst_keys`.`id` = `inst_configs`.`key_id`
WHERE `inst_sources`.`driver` = 'koha'
  AND `inst_keys`.`key_name` = 'agency';

-- Update DB version
UPDATE `system` SET `value` = '103' WHERE `key`='DB_VERSION';

-- Issue 522: Czech sorting for favorites
ALTER TABLE `resource`
  CHANGE `title` `title` varchar(255) COLLATE 'utf8mb4_czech_ci' NOT NULL DEFAULT '' AFTER `record_id`,
  CHANGE `author` `author` varchar(255) COLLATE 'utf8mb4_czech_ci' NULL AFTER `title`;

-- Update DB version
UPDATE `system` SET `value` = '104' WHERE `key`='DB_VERSION';


-- Issue 527: Update record identifiers for ARL
UPDATE `resource` SET `record_id` = REPLACE(`record_id`, 'kjm_us_cat', 'KjmUsCat') WHERE `record_id` LIKE "%kjm_us_cat%";
UPDATE `resource` SET `record_id` = REPLACE(`record_id`, 'li_us_cat', 'LiUsCat') WHERE `record_id` LIKE "%li_us_cat%";
UPDATE `resource` SET `record_id` = REPLACE(`record_id`, 'cbvk_us_cat', 'CbvkUsCat') WHERE `record_id` LIKE "%cbvk_us_cat%";
UPDATE `resource` SET `record_id` = REPLACE(`record_id`, 'kl_us_cat', 'KlUsCat') WHERE `record_id` LIKE "%kl_us_cat%";
UPDATE `resource` SET `record_id` = REPLACE(`record_id`, 'vy_us_cat', 'VyUsCat') WHERE `record_id` LIKE "%vy_us_cat%";

UPDATE `record` SET `record_id` = REPLACE(`record_id`, 'kjm_us_cat', 'KjmUsCat') WHERE `record_id` LIKE "%kjm_us_cat%";
UPDATE `record` SET `record_id` = REPLACE(`record_id`, 'li_us_cat', 'LiUsCat') WHERE `record_id` LIKE "%li_us_cat%";
UPDATE `record` SET `record_id` = REPLACE(`record_id`, 'cbvk_us_cat', 'CbvkUsCat') WHERE `record_id` LIKE "%cbvk_us_cat%";
UPDATE `record` SET `record_id` = REPLACE(`record_id`, 'kl_us_cat', 'KlUsCat') WHERE `record_id` LIKE "%kl_us_cat%";
UPDATE `record` SET `record_id` = REPLACE(`record_id`, 'vy_us_cat', 'VyUsCat') WHERE `record_id` LIKE "%vy_us_cat%";

UPDATE `widget_content` SET `value` = REPLACE(`value`, 'kjm_us_cat', 'KjmUsCat') WHERE `value` LIKE "%kjm_us_cat%";
UPDATE `widget_content` SET `value` = REPLACE(`value`, 'li_us_cat', 'LiUsCat') WHERE `value` LIKE "%li_us_cat%";
UPDATE `widget_content` SET `value` = REPLACE(`value`, 'cbvk_us_cat', 'CbvkUsCat') WHERE `value` LIKE "%cbvk_us_cat%";
UPDATE `widget_content` SET `value` = REPLACE(`value`, 'kl_us_cat', 'KlUsCat') WHERE `value` LIKE "%kl_us_cat%";
UPDATE `widget_content` SET `value` = REPLACE(`value`, 'vy_us_cat', 'VyUsCat') WHERE `value` LIKE "%vy_us_cat%";

UPDATE `resource` SET `record_id` = REPLACE(`record_id`, 'KjmUsCat_', 'KjmUsCat*') WHERE `record_id` LIKE "%KjmUsCat\\_%";
UPDATE `resource` SET `record_id` = REPLACE(`record_id`, 'LiUsCat_', 'LiUsCat*') WHERE `record_id` LIKE "%LiUsCat\\_%";
UPDATE `resource` SET `record_id` = REPLACE(`record_id`, 'CbvkUsCat_', 'CbvkUsCat*') WHERE `record_id` LIKE "%CbvkUsCat\\_%";
UPDATE `resource` SET `record_id` = REPLACE(`record_id`, 'KlUsCat_', 'KlUsCat*') WHERE `record_id` LIKE "%KlUsCat\\_%";
UPDATE `resource` SET `record_id` = REPLACE(`record_id`, 'VyUsCat_', 'VyUsCat*') WHERE `record_id` LIKE "%VyUsCat\\_%";

UPDATE `record` SET `record_id` = REPLACE(`record_id`, 'KjmUsCat_', 'KjmUsCat*') WHERE `record_id` LIKE "%KjmUsCat\\_%";
UPDATE `record` SET `record_id` = REPLACE(`record_id`, 'LiUsCat_', 'LiUsCat*') WHERE `record_id` LIKE "%LiUsCat\\_%";
UPDATE `record` SET `record_id` = REPLACE(`record_id`, 'CbvkUsCat_', 'CbvkUsCat*') WHERE `record_id` LIKE "%CbvkUsCat\\_%";
UPDATE `record` SET `record_id` = REPLACE(`record_id`, 'KlUsCat_', 'KlUsCat*') WHERE `record_id` LIKE "%KlUsCat\\_%";
UPDATE `record` SET `record_id` = REPLACE(`record_id`, 'VyUsCat_', 'VyUsCat*') WHERE `record_id` LIKE "%VyUsCat\\_%";

UPDATE `widget_content` SET `value` = REPLACE(`value`, 'KjmUsCat_', 'KjmUsCat*') WHERE `value` LIKE "%KjmUsCat\\_%";
UPDATE `widget_content` SET `value` = REPLACE(`value`, 'LiUsCat_', 'LiUsCat*') WHERE `value` LIKE "%LiUsCat\\_%";
UPDATE `widget_content` SET `value` = REPLACE(`value`, 'CbvkUsCat_', 'CbvkUsCat*') WHERE `value` LIKE "%CbvkUsCat\\_%";
UPDATE `widget_content` SET `value` = REPLACE(`value`, 'KlUsCat_', 'KlUsCat*') WHERE `value` LIKE "%KlUsCat\\_%";
UPDATE `widget_content` SET `value` = REPLACE(`value`, 'VyUsCat_', 'VyUsCat*') WHERE `value` LIKE "%VyUsCat\\_%";

-- Update DB version
UPDATE `system` SET `value` = '105' WHERE `key`='DB_VERSION';

-- #616: Remove eknihy_ke_stazeni from widgets
DELETE FROM `widget` WHERE `name` = 'eknihy_ke_stazeni';
UPDATE `system` SET `value` = '106' WHERE `key`='DB_VERSION';

-- #533: Add feedback table
CREATE TABLE `feedback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `message` longtext,
  `form_data` json DEFAULT NULL,
  `form_name` varchar(255) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `site_url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created` (`created`),
  KEY `status` (`status`(191)),
  KEY `form_name` (`form_name`(191)),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
UPDATE `system` SET `value` = '107' WHERE `key`='DB_VERSION';

-- #164: user setting
UPDATE citation_style SET value = NULL WHERE id = 2;
INSERT INTO citation_style(id, description, value) VALUES (38673, 'ČSN ISO 690', '38673');
UPDATE user_settings SET citation_style = 38673 WHERE citation_style = 2;
DELETE FROM citation_style WHERE id = 2;

-- #683: short loans
SET @mzk_source_id = (SELECT id FROM inst_sources WHERE source = 'mzk');
INSERT INTO `inst_sections` (`section_name`) VALUES('ShortLoan');
INSERT INTO `inst_keys` (`key_name`, `section_id`) VALUES ('enabled', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'ShortLoan'));
SELECT @short_loan_enabled_id := LAST_INSERT_ID();
INSERT INTO inst_configs (source_id, key_id, value, timestamp) VALUES (@mzk_source_id, @short_loan_enabled_id, 'true', NOW());
INSERT INTO `inst_sections` (`section_name`) VALUES('ShortLoanLinks');
INSERT INTO `inst_keys` (`key_name`, `section_id`) VALUES ('MZK01-000680703', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'ShortLoanLinks'));
SELECT @study_room_id := LAST_INSERT_ID();
INSERT INTO inst_configs (source_id, key_id, value, timestamp) VALUES (@mzk_source_id, @study_room_id, 'Team study room', NOW());
