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
