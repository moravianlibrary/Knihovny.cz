-- Add SQL update scripts here. Don't forget to add revision number after each script. Example below:
-- UPDATE `system` SET `value` = '101' WHERE `key`='DB_VERSION';

-- Issue 501
DELETE FROM `config_items` WHERE (`name` = 'citation_local_domain');
UPDATE `system` SET `value` = '101' WHERE `key`='DB_VERSION';
