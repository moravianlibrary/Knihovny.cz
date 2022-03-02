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
ALTER TABLE `widget_content` ADD FOREIGN KEY (`widget_id`) REFERENCES `widget` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

UPDATE `system` SET `value` = '102' WHERE `key`='DB_VERSION';
