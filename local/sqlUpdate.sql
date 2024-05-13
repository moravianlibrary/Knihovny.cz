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
UPDATE `system` SET `value` = '108' WHERE `key`='DB_VERSION';

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
UPDATE `system` SET `value` = '109' WHERE `key`='DB_VERSION';

-- #166: online payments
INSERT INTO `inst_sections` (`section_name`) VALUES('Payment');
INSERT INTO `inst_keys` (`key_name`, `section_id`)
  VALUES ('url', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Payment'));
SELECT @payment_link := LAST_INSERT_ID();
SET @mzk_source_id = (SELECT id FROM inst_sources WHERE source = 'mzk');
INSERT INTO inst_configs (source_id, key_id, value, timestamp)
  VALUES (@mzk_source_id, @payment_link, 'https://aleph.mzk.cz/cgi-bin/c-gpe1-vufind.pl', NOW());
UPDATE `system` SET `value` = '110' WHERE `key`='DB_VERSION';

-- #165: prolong registration
INSERT INTO `inst_sections` (`section_name`) VALUES('ProlongRegistration');
SELECT @prolong_registration_section_id := LAST_INSERT_ID();
INSERT INTO `inst_keys` (`key_name`, `section_id`) VALUES ('url', @prolong_registration_section_id);
SELECT @prolong_registration_url_id := LAST_INSERT_ID();
INSERT INTO `inst_keys` (`key_name`, `section_id`) VALUES ('status', @prolong_registration_section_id);
SELECT @prolong_registration_status_id := LAST_INSERT_ID();
INSERT INTO `inst_keys` (`key_name`, `section_id`) VALUES ('from', @prolong_registration_section_id);
SELECT @prolong_registration_from_id := LAST_INSERT_ID();
INSERT INTO `inst_keys` (`key_name`, `section_id`) VALUES ('hmac', @prolong_registration_section_id);
SELECT @prolong_registration_hmac_id := LAST_INSERT_ID();
SET @mzk_source_id = (SELECT id FROM inst_sources WHERE source = 'mzk');
INSERT INTO inst_configs (source_id, key_id, value, timestamp) VALUES (@mzk_source_id, @prolong_registration_url_id, 'http://aleph.mzk.cz/cgi-bin/prodl_reg.pl', NOW());
INSERT INTO inst_configs (source_id, key_id, value, timestamp) VALUES (@mzk_source_id, @prolong_registration_status_id, '03', NOW());
INSERT INTO inst_configs (source_id, key_id, value, timestamp) VALUES (@mzk_source_id, @prolong_registration_from_id, 'cpk', NOW());
INSERT INTO inst_configs (source_id, key_id, value, timestamp) VALUES (@mzk_source_id, @prolong_registration_hmac_id, 'rayedhmVaiU7', NOW());
UPDATE `system` SET `value` = '111' WHERE `key`='DB_VERSION';

-- #32: Add helpText inst config key
ALTER TABLE `inst_configs` ADD `array_key` varchar(191) DEFAULT NULL COMMENT 'Klíč pole (nepovinné)' AFTER  `key_id`;

INSERT INTO `inst_keys` (`key_name`, `section_id`) VALUES
('helpText', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Holds'));

-- Czech texts
INSERT INTO `inst_configs` (`source_id`, `key_id`, `array_key`, `value`) VALUES
  ((SELECT `id` FROM `inst_sources` WHERE `source` = 'mzk'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'helpText'), 'cs', '<p>Pokud vyplňujete tento formulář pro <strong>publikaci, která je právě vypůjčená</strong> jiným čtenářem, provede se <strong>rezervace</strong> dané publikace. Publikaci si budete moci vypůjčit, jakmile ji vrátí čtenáři před Vámi (jejich počet vidíte v poli &quot;Vaše pořadí ve frontě&quot;).</p>

<p>Vyplňujete-li tento formulář pro <strong>volnou publikaci, kterou si chcete nechat donést ze skladu nebo odložit na pobočce</strong>, provede se <strong>objednávka</strong> dané publikace.</p>

<p><strong>Objednávky zadané po 18:00</strong> v pracovní dny a po celý den <strong>v sobotu a neděli</strong> budou vyřízeny až <strong>ráno následujícího pracovního dne</strong>.</p>

<p>Pokud si objednáváte knihu <strong>z depozitáře</strong>, kde je <strong>doba vyhledání 10 dní</strong>, <strong>prodlužte si, prosím, dobu zájmu o výpůjčku</strong>.</p>

<p>Ve chvíli, kdy bude Vaše publikace nachystaná na vyzvednutí, budete informováni e-mailem.</p>'),
  ((SELECT `id` FROM `inst_sources` WHERE `source` = 'svkhk'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'helpText'), 'cs', '<p>Pokud vyplňujete tento formulář pro <strong>publikaci, která je právě vypůjčená</strong> jiným čtenářem, provede se <strong>rezervace</strong> dané publikace. Publikaci si budete moci vypůjčit, jakmile ji vrátí čtenáři před Vámi (jejich počet vidíte v poli &quot;Vaše pořadí ve frontě&quot;).</p>

<p>Vyplňujete-li tento formulář pro <strong>volnou publikaci, kterou si chcete nechat donést ze skladu nebo odložit na pobočce</strong>, provede se <strong>objednávka</strong> dané publikace.</p>

<p>Objednávky zadané na sbírku <strong>Ext.sklad - 2. den po 14. hod.</strong> budou vyřízeny <strong>následující pracovní den po 14. hodině</strong>.</p>

<p><strong>Požadavky zadané v pracovní den po 18.30 a o víkendu</strong>, začínají být <strong>zpracovávány až následující pracovní den</strong> a dodány jsou až <strong>další den po 14. hodině.</strong><br />
Např. objednáte-li si knihu nebo periodikum v sobotu, budete ji mít k dispozici až v úterý odpoledne.</p>'),
  ((SELECT `id` FROM `inst_sources` WHERE `source` = 'iir'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'helpText'), 'cs', '<p>Pokud si objednáváte<strong> dostupnou jednotku v oddělení ÚMV sklad</strong>, publikace pro Vás bude přichystána <strong>následující výpůjční den</strong>.</p>'),
  ((SELECT `id` FROM `inst_sources` WHERE `source` = 'kvkl'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'helpText'), 'cs', '<p>Pokud vyplňujete tento formulář pro <strong>publikaci, která je právě vypůjčená</strong> jiným čtenářem, provede se <strong>rezervace</strong> dané publikace. Publikaci si budete moci vypůjčit, jakmile ji vrátí čtenáři před Vámi (jejich počet vidíte v poli &quot;Vaše pořadí ve frontě&quot;).</p>

<p>Vyplňujete-li tento formulář pro <strong>volnou publikaci, kterou si chcete nechat donést ze skladu nebo odložit na pobočce</strong>, provede se <strong>objednávka</strong> dané publikace.</p>

<p><strong>Vyžádat (objednat)</strong> si můžete pouze ty dokumenty, které jsou umístěny <strong>ve skladu</strong>.</p>

<ul>
	<li>Vyžádané dokumenty si můžete <strong>vyzvednout ve Studijní knihovně (1. patro) za 20 minut, nejpozději do 48 hodin od doby vyžádání</strong>.</li>
	<li>Žádanky odeslané v době <strong>od 18.30 hod.</strong> budou připraveny <strong>k vyzvednutí další výpůjční den</strong>.</li>
	<li>Jeden uživatel si může <strong>objednat maximálně 10 dokumentů (s výjimkou časopisů, kde je maximum 5 ročníků)</strong> a to pouze těch, které nejsou vypůjčené.</li>
</ul>

<p><strong>Rezervovat</strong> si můžete pouze ty dokumenty, které nemají momentálně volný exemplář, to znamená, že <strong>jsou všechny vypůjčené</strong>.</p>

<ul>
	<li>Za rezervaci se platí <strong>poplatek 7 Kč.</strong></li>
  <li>Za objednávku z volného výběru se platí <strong>poplatek 10 Kč.</strong></li>
  <li>Za objednávku ze skladu se poplatek <strong>neplatí.</strong></li>
  <li>Ve chvíli, kdy bude Váš rezervovaný dokument připraven k vyzvednutí <strong>budete informováni e-mailem či SMS zprávou</strong>.</li>
</ul>'),
  ((SELECT `id` FROM `inst_sources` WHERE `source` = 'mkkh'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'helpText'), 'cs', '<p>Pokud vyplňujete tento formulář pro <strong>publikaci, která je právě vypůjčená</strong> jiným čtenářem, provede se <strong>rezervace</strong> dané publikace. Publikaci si budete moci vypůjčit, jakmile ji vrátí čtenáři před Vámi (jejich počet vidíte v poli &quot;Vaše pořadí ve frontě&quot;).</p>

<p>Vyplňujete-li tento formulář pro <strong>volnou publikaci, kterou si chcete nechat odložit</strong>, provede se <strong>objednávka</strong> dané publikace.</p>

<p><strong>Rezervace jsou zpoplatněny dle platného knihovního řádu.</strong><br />
Objednávky bývají zpravidla vyřízeny do následujícího pracovního dne (v případě poboček maximálně do 5 pracovních dnů).<br />
Rezervované a objednané publikace necháváme <strong>připraveny po dobu 14 dní</strong>.</p>'),
  ((SELECT `id` FROM `inst_sources` WHERE `source` = 'mkp'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'helpText'), 'cs', '<p><strong>Rezervace je placená služba.</strong> Poplatek za rezervaci se platí po jejím splnění.<br />
<strong>O splnění rezervace knihovna informuje e-mailem (10 Kč) nebo poštou (25 Kč)</strong>.<br />
Rezervovat si můžete absenční tituly dostupné právě teď v knihovně, vypůjčené, rezervované, v oběhu nebo ve vaší pobočce nepřítomné.</p>

<p><strong>Pobočku, kde si chcete knihu půjčit, zvolíte v kolonce místo vyzvednutí.</strong><br />
Rezervace je připravena k vyzvednutí až po oznámení.<br />
Stav svých rezervací můžete sledovat v on-line čtenářském kontě.</p>

<p><strong>Zvolte, do kdy máte o rezervaci zájem.</strong> Zadáním konkrétního data se vyhnete tomu, že se rezervace splní v době, kdy už knihu nepotřebujete. <br />
Doba splnění rezervace je závislá na tom, zda je kniha přítomná v knihovně nebo zda se čeká na její vracení nebo převoz na vybranou pobočku.</p>

<p><strong>Oznámení se standardně zasílá na e-mail</strong> uvedený v kontaktních údajích čtenáře.<br />
<strong>Rezervace v knihovně čeká 5 provozních dnů.</strong></p>

<p>Je možné zvolit si <strong>zaslání oznámení poštou.</strong> <strong>Tato volba není dostupná na portálu Knihovny.cz</strong> - máte-li zájem o tuto službu, <strong>použijte online katalog Městské knihovny v Praze</strong>.<br />
<strong>Rezervace pak v knihovně čeká 10 provozních dnů</strong> a k poplatku za splnění rezervace se účtuje <strong>15 Kč za poštovné</strong> (tj. dohromady 25 Kč).</p>'),
  ((SELECT `id` FROM `inst_sources` WHERE `source` = 'svkul'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'helpText'), 'cs', '<p>Pokud vyplňujete tento formulář <strong>pro dokument, který je právě vypůjčený</strong> jiným čtenářem, <strong>provede se rezervace</strong> daného dokumentu. Dokument si budete moci vypůjčit, jakmile ji vrátí čtenáři před Vámi (jejich počet vidíte v poli &quot;Vaše pořadí ve frontě&quot;).</p>

<p>Vyplňujete-li tento formulář pro <strong>volný dokument</strong>, který si chcete nechat donést <strong>ze skladu</strong>, <strong>provede se objednávka</strong> daného dokumentu. Objednávky zadané <strong>po 18:00 v pracovní dny a po celý den v sobotu a neděli budou vyřízeny až ráno následujícího pracovního dne</strong>.</p>'),
  ((SELECT `id` FROM `inst_sources` WHERE `source` = 'tre'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'helpText'), 'cs', '<p>Pokud vyplňujete tento formulář pro <strong>publikaci, která je právě vypůjčená</strong> jiným čtenářem, provede se <strong>rezervace</strong> dané publikace. Publikaci si budete moci vypůjčit, jakmile ji vrátí čtenáři před Vámi (jejich počet vidíte v poli &quot;Vaše pořadí ve frontě&quot;).<br />
Vyzvednout si je můžete jakmile <strong>obdržíte potvrzení e-mailem nebo SMS</strong>.<br />
Od té doby máte <strong>5 pracovních dní na jejich vyzvednutí.</strong></p>

<p>Vyplňujete-li tento formulář pro <strong>volnou publikaci, kterou si chcete nechat donést ze skladu nebo odložit na pobočce</strong>, provede se <strong>objednávka</strong> dané publikace.<br />
Dokumenty Vám <strong>načteme jako výpůjčku a pošleme potvrzující e-mail</strong>.<br />
Od té doby máte <strong>3 pracovní dny na jejich vyzvednutí</strong>.</p>'),
  ((SELECT `id` FROM `inst_sources` WHERE `source` = 'vkta'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'helpText'), 'cs', '<p>Pokud vyplňujete tento formulář pro <strong>publikaci, která je právě vypůjčená</strong> jiným čtenářem, provede se <strong>rezervace</strong> dané publikace. Publikaci si budete moci vypůjčit, jakmile ji vrátí čtenáři před Vámi (jejich počet vidíte v poli &quot;Vaše pořadí ve frontě&quot;).</p>

<p>Vyplňujete-li tento formulář pro <strong>volnou publikaci, kterou si chcete nechat donést ze skladu nebo odložit na pobočce</strong>, provede se <strong>objednávka</strong> dané publikace.</p>

<p>O tom, že je kniha připravena k vyzvednutí, dostane čtenář potvrzující zprávu.</p>

<p><strong>Objednávka je k dispozici dva provozní dny.</strong></p>

<p><strong>Rezervace je k dispozici 7 provozních dnů</strong></p>');

-- English texts
INSERT INTO `inst_configs` (`source_id`, `key_id`, `array_key`, `value`) VALUES
((SELECT `id` FROM `inst_sources` WHERE `source` = 'mzk'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'helpText'), 'en', '<p>If you are filling out this form for a <strong>publication that is currently borrowed</strong> by another reader, the <strong>reservation</strong> of the given publication will be made. You will be able to borrow the publication as soon as the readers before you have returned it (you can see their number in the field &quot;Your position in the queue&quot;).</p>

<p>If you are filling out this form for a <strong>currently available publication that you want to have delivered from the warehouse or left at the branch</strong>, an <strong>order</strong> for the given publication will be placed.</p>

<p><strong>Orders placed after 18:00</strong> on working days and all day on <strong>saturdays and sundays</strong> will be processed <strong>in the morning of the following working day</strong>.</p>

<p>If you are ordering a book <strong>from a depository</strong>, where the <strong>search time is 10 days</strong>, <strong>please extend the period of interest</strong>.</p>

<p>You will be notified by e-mail when your publication is ready to pick up.</p>');

UPDATE `system` SET `value` = '112' WHERE `key`='DB_VERSION';

-- Issue 714
DROP TABLE IF EXISTS `widget_categories`;
CREATE TABLE `widget_categories` (
  `category` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kategorie',
  `description` mediumtext NOT NULL COMMENT 'Popis kategorie',
  PRIMARY KEY (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci COMMENT='Inspirační seznamy - kategorie';

INSERT INTO `widget_categories` (`category`, `description`) VALUES
  ('authors',	'Autoři'),
  ('awards',	'Literární ceny'),
  ('children',	'Pro děti'),
  ('ebooks',	'E-knihy'),
  ('fiction',	'Beletrie'),
  ('history',	'Historie'),
  ('hobby',	'Koníčky'),
  ('nonfiction',	'Naučná literatura'),
  ('places',	'Místa'),
  ('season',	'Roční období');

ALTER TABLE `widget`
  ADD `category` varchar(191) COLLATE 'utf8mb4_unicode_ci' NOT NULL COMMENT 'Kategorie';

ALTER TABLE `widget`
  ADD FOREIGN KEY (`category`) REFERENCES `widget_categories` (`category`) ON DELETE RESTRICT ON UPDATE CASCADE

UPDATE `system` SET `value` = '113' WHERE `key`='DB_VERSION';

-- #738: Remove widgets
ALTER TABLE `user_list`
  ADD `category` varchar(191) COLLATE 'utf8mb4_unicode_ci' NOT NULL DEFAULT '',
  ADD `old_name` varchar(40) COLLATE 'utf8mb4_unicode_ci' NOT NULL DEFAULT '';
INSERT INTO `widget_categories` (`category`, `description`) VALUES ('', '');

CREATE TABLE `user_list_categories` LIKE `widget_categories`;
INSERT INTO `user_list_categories` SELECT * FROM `widget_categories`;

ALTER TABLE `user_list`
  ADD FOREIGN KEY (`category`) REFERENCES `user_list_categories` (`category`) ON DELETE RESTRICT ON UPDATE CASCADE;

INSERT INTO user_list (user_id, title, description, public, category, old_name)
SELECT (SELECT user_id FROM user_card WHERE cat_username="mzk.701") AS user_id, title_cs AS title, description AS description, 1 AS public, category AS category, `name` AS `old_name`
FROM widget;

INSERT INTO resource (record_id, source)
SELECT value AS record_id, "Solr" AS source
FROM widget_content;

INSERT INTO user_resource (user_id, resource_id, list_id)
SELECT
  (SELECT user_id FROM user_card WHERE cat_username="mzk.701") AS user_id,
  r.id AS resource_id,
  ul.id AS list_id
FROM resource r
       JOIN widget_content wc ON r.record_id = wc.value
       JOIN widget w ON wc.widget_id = w.id
       JOIN user_list ul ON ul.title = w.title_cs;

ALTER TABLE `config` ADD `widget_name` varchar(191) NOT NULL;

UPDATE `config`
SET `widget_name` = SUBSTRING_INDEX(SUBSTRING_INDEX(`value`, ':', 2), ':', -1)
WHERE `value` LIKE 'Inspiration:%';

UPDATE `config`
  JOIN `widget`
ON `widget`.`name` = `config`.`widget_name`
  JOIN `user_list`
  ON `widget`.`title_cs` = `user_list`.`title`
  SET `config`.`value` = IF(SUBSTRING(`config`.`value`,  -3) LIKE "%:%", CONCAT('UserList:', `user_list`.`id`, ':', SUBSTRING_INDEX(`config`.`value`, ':', -1)), CONCAT('UserList:', `user_list`.`id`))
WHERE `config`.`value` LIKE 'Inspiration:%';

ALTER TABLE `config` DROP `widget_name`;

UPDATE `system` SET `value` = '114' WHERE `key`='DB_VERSION';

-- Issue 777: Add ratings table
CREATE TABLE `ratings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `resource_id` int(11) NOT NULL DEFAULT '0',
    `rating` int(3) NOT NULL,
    `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `resource_id` (`resource_id`),
    CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
    CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Only for views db:
CREATE VIEW `ratings` AS
SELECT `vufind`.`ratings`.`id` AS `id`,
       `vufind`.`ratings`.`user_id` AS `user_id`,
       `vufind`.`ratings`.`resource_id` AS `resource_id`,
       `vufind`.`ratings`.`rating` AS `rating`,
       `vufind`.`ratings`.`created` AS `created`
FROM `vufind`.`ratings`;

UPDATE `system` SET `value` = '115' WHERE `key`='DB_VERSION';

ALTER TABLE `resource` ADD COLUMN author_sort VARCHAR(255) COLLATE 'utf8mb4_czech_ci' NULL AFTER `author`;
UPDATE `system` SET `value` = '116' WHERE `key`='DB_VERSION';

-- #761
INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
SELECT `id`,
       (SELECT `inst_keys`.`id` FROM `inst_keys` JOIN `inst_sections` ON `inst_keys`.`section_id` = `inst_sections`.`id`
        WHERE `inst_keys`.`key_name` = 'enabled' AND `inst_sections`.`section_name` = 'TransactionHistory'),
       '1'
FROM `inst_sources` WHERE `driver` = 'koha';

INSERT INTO `inst_keys` (`key_name`, `section_id`) VALUES ('updateFields', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Holds'));

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
  (
    (SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'),
    (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'HMACKeys'),
    'item_id:holdtype:level'
  );

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
  (
    (SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'),
    (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'extraHoldFields'),
    'requiredByDate:pickUpLocation'
  );

UPDATE `system` SET `value` = '117' WHERE `key`='DB_VERSION';

-- #809
ALTER TABLE `user_list`
DROP FOREIGN KEY `user_list_ibfk_4`,
ADD FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

UPDATE `system` SET `value` = '118' WHERE `key`='DB_VERSION';

-- #806
ALTER TABLE `search` CHANGE `saved` `saved` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `user_list` CHANGE `public` `public` tinyint(1) NOT NULL DEFAULT '0';

UPDATE `system` SET `value` = '119' WHERE `key`='DB_VERSION';

-- #752
DROP TABLE `widget`;
DROP TABLE `widget_categories`;
DROP TABLE `widget_content`;

UPDATE `system` SET `value` = '120' WHERE `key`='DB_VERSION';

CREATE TABLE `record_status` (
    `record_id`        varchar(255) NOT NULL,
    `absent_total`     int(11) NOT NULL DEFAULT 0,
    `absent_on_loan`   int(11) NOT NULL DEFAULT 0,
    `present_total`    int(11) NOT NULL DEFAULT 0,
    `present_on_loan`  int(11) NOT NULL DEFAULT 0,
    `last_update`      bigint NOT NULL DEFAULT 0,
    PRIMARY KEY (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `import_record_status_totals` (
  `record_id`        varchar(255) NOT NULL,
  `source`           varchar(32),
  `absent_total`     int(11) NOT NULL DEFAULT 0,
  `present_total`    int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `import_record_status_loans` (
  `record_id`          varchar(255) NOT NULL,
  `source`             varchar(32),
  `absent_on_loan`     int(11) NOT NULL DEFAULT 0,
  `present_on_loan`    int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

UPDATE `system` SET `value` = '121' WHERE `key`='DB_VERSION';

-- #940
INSERT INTO `inst_keys` (`key_name`, `section_id`)
VALUES ('itemUseRestrictionTypesForStatus', (SELECT id FROM `inst_sections` WHERE `section_name` = 'Catalog'));

INSERT INTO inst_configs (`source_id`, `key_id`,  `value`) VALUES (
  (SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'),
  (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'itemUseRestrictionTypesForStatus'),
  'In Library Use Only'
);

INSERT INTO inst_configs (`source_id`, `key_id`,  `value`) VALUES (
  (SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'),
  (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'itemUseRestrictionTypesForStatus'),
  'Not For Loan'
);

UPDATE `system` SET `value` = '122' WHERE `key`='DB_VERSION';

