--
-- Aktualizace tabulky inst_configs
--
ALTER TABLE `inst_configs`
    COMMENT='Konfigurace knihoven',
    CHANGE `source` `source` varchar(10) COLLATE 'utf8_general_ci' NOT NULL DEFAULT '' COMMENT 'Knihovna (source)' AFTER `id`,
    CHANGE `section` `section` varchar(64) COLLATE 'utf8_general_ci' NOT NULL COMMENT 'Sekce' AFTER `source`,
    CHANGE `key` `key` varchar(64) COLLATE 'utf8_general_ci' NOT NULL COMMENT 'Klíč' AFTER `section`,
    CHANGE `value` `value` mediumtext COLLATE 'utf8_general_ci' NOT NULL COMMENT 'Hodnota' AFTER `key`;

--
-- Vytvoření a naplnění sources
--
DROP TABLE IF EXISTS `inst_sources`;
CREATE TABLE `inst_sources` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `library_name` varchar(191) NOT NULL DEFAULT '' COMMENT 'Název knihovny',
    `source` varchar(191) NOT NULL COMMENT 'Knihovna (source)',
    `driver` varchar(191) NOT NULL DEFAULT '' COMMENT 'ILS driver',
    PRIMARY KEY (`id`),
    UNIQUE KEY `source` (`source`),
    KEY `driver` (`driver`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Knihovny';

INSERT INTO `inst_sources` (`source`) SELECT DISTINCT `source` FROM `inst_configs`;
ALTER TABLE `inst_configs` ADD `source_id` int NOT NULL COMMENT 'Knihovna (source)' AFTER `source`;
UPDATE inst_configs c JOIN inst_sources s ON c.source = s.source SET c.source_id = s.id;

ALTER TABLE `inst_configs`
    ADD FOREIGN KEY (`source_id`) REFERENCES `inst_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `inst_configs` DROP `source`;

-- Create templates for inst_configs
INSERT INTO `inst_sources` (`source`) VALUES
('!aleph'), ('!koha'), ('!ncip');

-- Define drivers
UPDATE `inst_sources` SET `driver` = 'aleph' WHERE `source` IN ('cvgz', 'kkpc', 'knav', 'mzk', 'nkp', 'ntk', 'svkhk', 'svkos', 'svkpk', 'uzei', 'vkol', '!aleph');
UPDATE `inst_sources` SET `driver` = 'koha' WHERE `source` IN ('mkbohumin', 'mkdb', 'mklit', 'mkmt', 'mkuo', 'tre', 'vkta', '!koha');
UPDATE `inst_sources` SET `driver` = 'caslin' WHERE `source` = 'caslin';
UPDATE `inst_sources` SET `driver` = 'ncip' WHERE `driver` = '';

--
-- Add library names (based on https://github.com/moravianlibrary/CPK/blob/master/local/knihovny/config/vufind/shibboleth.ini)
--
UPDATE inst_sources SET library_name = "Souborný katalog ČR (caslin)" WHERE source = "caslin";
UPDATE inst_sources SET library_name = "Jihočeská vědecká knihovna v Českých Budějovicích (cbvk)" WHERE source = "cbvk";
UPDATE inst_sources SET library_name = "Centrum výzkumu globální změny AV ČR (cvgz)" WHERE source = "cvgz";
UPDATE inst_sources SET library_name = "Ústav mezinárodních vztahů (iir)" WHERE source = "iir";
UPDATE inst_sources SET library_name = "Krajská knihovna Františka Bartoše ve Zlíně (kfbz)" WHERE source = "kfbz";
UPDATE inst_sources SET library_name = "Knihovna Jana Drdy Příbram (kjdpb)" WHERE source = "kjdpb";
UPDATE inst_sources SET library_name = "Knihovna Jiřího Mahena (kjm)" WHERE source = "kjm";
UPDATE inst_sources SET library_name = "Knihovna Karla Dvořáčka Vyškov (kkdvy)" WHERE source = "kkdvy";
UPDATE inst_sources SET library_name = "Krajská knihovna Karlovy Vary (kkkv)" WHERE source = "kkkv";
UPDATE inst_sources SET library_name = "Krajská knihovna v Pardubicích (kkpc)" WHERE source = "kkpc";
UPDATE inst_sources SET library_name = "Krajská knihovna Vysočiny (kkvy)" WHERE source = "kkvy";
UPDATE inst_sources SET library_name = "Knihovna Akademie věd ČR (knav)" WHERE source = "knav";
UPDATE inst_sources SET library_name = "Knihovna Eduarda Petišky (knep)" WHERE source = "knep";
UPDATE inst_sources SET library_name = "Knihovna Kroměřížska (knihkm)" WHERE source = "knihkm";
UPDATE inst_sources SET library_name = "Krajská vědecká knihovna v Liberci (kvkl)" WHERE source = "kvkl";
UPDATE inst_sources SET library_name = "Mendelova univerzita v Brně (mendelu)" WHERE source = "mendelu";
UPDATE inst_sources SET library_name = "Městská knihovna Břeclav (mkbrec)" WHERE source = "mkbrec";
UPDATE inst_sources SET library_name = "Městská knihovna v Českém Krumlově (mkck)" WHERE source = "mkck";
UPDATE inst_sources SET library_name = "Městská knihovna Česká Lípa (mkcl)" WHERE source = "mkcl";
UPDATE inst_sources SET library_name = "Knihovna města Hradce Králové (mkhk)" WHERE source = "mkhk";
UPDATE inst_sources SET library_name = "Městská knihovna Hradec nad Moravicí (mkhnm)" WHERE source = "mkhnm";
UPDATE inst_sources SET library_name = "Městská knihovna Hodonín (mkhod)" WHERE source = "mkhod";
UPDATE inst_sources SET library_name = "Městská knihovna Holešov (mkhol)" WHERE source = "mkhol";
UPDATE inst_sources SET library_name = "Městská knihovna Chodov (mkchodov)" WHERE source = "mkchodov";
UPDATE inst_sources SET library_name = "Městská knihovna Jaroměř (mkjar)" WHERE source = "mkjar";
UPDATE inst_sources SET library_name = "Městská knihovna Kutná Hora (mkkh)" WHERE source = "mkkh";
UPDATE inst_sources SET library_name = "Městská knihovna Kladno (mkkl)" WHERE source = "mkkl";
UPDATE inst_sources SET library_name = "Městská knihovna Klatovy (mkklat)" WHERE source = "mkklat";
UPDATE inst_sources SET library_name = "Městská knihovna Kolín (mkkolin)" WHERE source = "mkkolin";
UPDATE inst_sources SET library_name = "Mětská knihovna v Milevsku (mkmil)" WHERE source = "mkmil";
UPDATE inst_sources SET library_name = "Městská knihovna Mariánské Lázně (mkml)" WHERE source = "mkml";
UPDATE inst_sources SET library_name = "Městská knihovna Most (mkmost)" WHERE source = "mkmost";
UPDATE inst_sources SET library_name = "Městská knihovna Orlová (mkor)" WHERE source = "mkor";
UPDATE inst_sources SET library_name = "Městská knihovna Ostrov (mkostrov)" WHERE source = "mkostrov";
UPDATE inst_sources SET library_name = "Městská knihovna v Praze (mkp)" WHERE source = "mkp";
UPDATE inst_sources SET library_name = "Městská knihovna Pelhřimov (mkpel)" WHERE source = "mkpel";
UPDATE inst_sources SET library_name = "Městská knihovna Písek (mkpisek)" WHERE source = "mkpisek";
UPDATE inst_sources SET library_name = "Knihovna města Plzně (mkplzen)" WHERE source = "mkplzen";
UPDATE inst_sources SET library_name = "Městská knihovna v Přerově (mkpr)" WHERE source = "mkpr";
UPDATE inst_sources SET library_name = "Městská knihovna ve Svitavách (mksvit)" WHERE source = "mksvit";
UPDATE inst_sources SET library_name = "Knihovna Třinec (mktri)" WHERE source = "mktri";
UPDATE inst_sources SET library_name = "Městská knihovna Trutnov (mktrut)" WHERE source = "mktrut";
UPDATE inst_sources SET library_name = "Městská knihovna Ústí nad Orlicí (mkuo)" WHERE source = "mkuo";
UPDATE inst_sources SET library_name = "Městská knihovna Znojmo (mkzn)" WHERE source = "mkzn";
UPDATE inst_sources SET library_name = "Moravská zemská knihovna (mzk)" WHERE source = "mzk";
UPDATE inst_sources SET library_name = "Národní knihovna ČR (nkp)" WHERE source = "nkp";
UPDATE inst_sources SET library_name = "Národní lékařská knihovna (nlk)" WHERE source = "nlk";
UPDATE inst_sources SET library_name = "Národní technická knihovna (ntk)" WHERE source = "ntk";
UPDATE inst_sources SET library_name = "Knihovna Petra Bezruče v Opavě (okpb)" WHERE source = "okpb";
UPDATE inst_sources SET library_name = "Regionální knihovna Karviná (rkka)" WHERE source = "rkka";
UPDATE inst_sources SET library_name = "Studijní a vědecká knihovna v Hradci Králové (svkhk)" WHERE source = "svkhk";
UPDATE inst_sources SET library_name = "Středočeská vědecká knihovna v Kladně (svkkl)" WHERE source = "svkkl";
UPDATE inst_sources SET library_name = "Moravskoslezská vědecká knihovna v Ostravě (svkos)" WHERE source = "svkos";
UPDATE inst_sources SET library_name = "Studijní a vědecká knihovna Plzeňského kraje (svkpk)" WHERE source = "svkpk";
UPDATE inst_sources SET library_name = "Severočeská vědecká knihovna v Ústí nad Labem (svkul)" WHERE source = "svkul";
UPDATE inst_sources SET library_name = "Městská knihovna Česká Třebová (tre)" WHERE source = "tre";
UPDATE inst_sources SET library_name = "Knihovna Antonína Švehly (uzei)" WHERE source = "uzei";
UPDATE inst_sources SET library_name = "Veterinární a farmaceutická univerzita Brno (vfu)" WHERE source = "vfu";
UPDATE inst_sources SET library_name = "Vědecká knihovna v Olomouci (vkol)" WHERE source = "vkol";
UPDATE inst_sources SET library_name = "Městská knihovna Tábor (vkta)" WHERE source = "vkta";
UPDATE inst_sources SET library_name = "Chomutovská knihovna (mkchom)" WHERE source = "mkchom";
UPDATE inst_sources SET library_name = "Městská knihovna Frýdek-Místek (mkfm)" WHERE source = "mkfm";
UPDATE inst_sources SET library_name = "Městská knihovna Ladislava z Boskovic v Moravské Třebové (mkmt)" WHERE source = "mkmt";
UPDATE inst_sources SET library_name = "Knihovna města Olomouce (kmol)" WHERE source = "kmol";
UPDATE inst_sources SET library_name = "Městská knihovna Rožnov pod Radhoštěm (knir)" WHERE source = "knir";
UPDATE inst_sources SET library_name = "K3 Bohumín - středisko KNIHOVNA (mkbohumin)" WHERE source = "mkbohumin";
UPDATE inst_sources SET library_name = "Městská knihovna Boskovice (mkboskovic)" WHERE source = "mkboskovic";
UPDATE inst_sources SET library_name = "Městská knihovny v Chebu (mkcheb)" WHERE source = "mkcheb";
UPDATE inst_sources SET library_name = "Městská knihovna Chrudim (mkchrudim)" WHERE source = "mkchrudim";
UPDATE inst_sources SET library_name = "Městská knihovna Jindřichův Hradec (mkjh)" WHERE source = "mkjh";
UPDATE inst_sources SET library_name = "Městská knihovna Litvínov (mklit)" WHERE source = "mklit";
UPDATE inst_sources SET library_name = "Husova knihovna Říčany (mkricany)" WHERE source = "mkricany";
UPDATE inst_sources SET library_name = "Městská knihovna v Třebíči (mktrebic)" WHERE source = "mktrebic";

UPDATE inst_sources SET `library_name` = `source` WHERE `library_name` = '';
--
-- Create configurations for NoILS drivers
--
INSERT INTO inst_sources(library_name, source, driver) VALUES ('!noils', '!noils', 'noils');
INSERT INTO inst_sources(library_name, source, driver) VALUES ('mojeID', 'mojeid', 'noils');
INSERT INTO inst_sources(library_name, source, driver) VALUES ('Google', 'google', 'noils');
INSERT INTO inst_sources(library_name, source, driver) VALUES ('Facebook', 'facebook', 'noils');
INSERT INTO inst_sources(library_name, source, driver) VALUES ('LinkedIn', 'linkedin', 'noils');

--
-- Vytvoření a naplnění sections
--
DROP TABLE IF EXISTS `inst_sections`;
CREATE TABLE `inst_sections` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `section_name` varchar(191) NOT NULL COMMENT 'Název sekce',
    PRIMARY KEY (`id`),
    KEY `section_name` (`section_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Knihovny - sekce';

INSERT INTO `inst_sections` (`section_name`) SELECT DISTINCT `section` FROM `inst_configs`;
ALTER TABLE `inst_configs` ADD `section_id` int NOT NULL COMMENT 'Sekce' AFTER `section`;
UPDATE `inst_configs` c JOIN `inst_sections` s ON c.`section` = s.`section_name` SET c.`section_id` = s.id;

ALTER TABLE `inst_configs` DROP `section`;

INSERT INTO `inst_sections` (`section_name`) VALUES
    ('StorageRetrievalRequests'),
    ('ItemStatusMappings'),
    ('Languages'),
    ('sublibadm'),
    ('settings');

--
-- Vytvoření a naplnění keys
--
DROP TABLE IF EXISTS `inst_keys`;
CREATE TABLE `inst_keys` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `key_name` varchar(191) NOT NULL COMMENT 'Název položky',
     `section_id` int(11) NOT NULL COMMENT 'Sekce',
     PRIMARY KEY (`id`),
     UNIQUE `key_name_section_id` (`key_name`, `section_id`),
     FOREIGN KEY (`section_id`) REFERENCES `inst_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Knihovny - položky konfigurace';

INSERT INTO `inst_keys` (`key_name`, `section_id`) SELECT DISTINCT `key`, `section_id` FROM inst_configs;
ALTER TABLE `inst_configs` ADD `key_id` int NOT NULL COMMENT 'Položka' AFTER `key`;
UPDATE inst_configs c JOIN inst_keys k ON c.`key` = k.key_name AND c.section_id = k.section_id SET c.key_id = k.id;

ALTER TABLE `inst_configs`
    ADD FOREIGN KEY (`key_id`) REFERENCES `inst_keys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `inst_configs` DROP `key`;
ALTER TABLE `inst_configs` DROP `section_id`;

UPDATE `inst_configs` SET `key_id` = (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'hmac_key')
WHERE `key_id` = (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'hmac_`key`');

DELETE FROM `inst_keys` WHERE (`key_name` = 'authMethod');
DELETE FROM `inst_keys` WHERE (`key_name` = 'cannotUseLUIS');
DELETE FROM `inst_keys` WHERE (`key_name` = 'contactPerson');
DELETE FROM `inst_keys` WHERE (`key_name` = 'debug');
DELETE FROM `inst_keys` WHERE (`key_name` = 'requester');
DELETE FROM `inst_keys` WHERE (`key_name` = 'ttl');
DELETE FROM `inst_keys` WHERE (`key_name` = 'type');
DELETE FROM `inst_keys` WHERE (`key_name` = 'hmac_`key`');
DELETE FROM `inst_keys` WHERE (`key_name` = 'grandType');

INSERT INTO `inst_keys` (`key_name`, `section_id`) VALUES
-- NCIP
('consortium', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Catalog')),
('fromAgency', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Catalog')),
('disableRenewals', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Catalog')),
('pickupLocationsFromNCIP', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Catalog')),
('transactionsHistory', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Catalog')),
('httpBasicAuth', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Catalog')),
('otherAcceptedHttpStatusCodes', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Catalog')),
-- Koha
('HMACKeys', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Holds')),
('defaultRequiredDate', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Holds')),
('extraHoldFields', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Holds')),
-- Aleph
('sublibadm', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'sublibadm')),
('dlfbaseurl', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Catalog')),
('cs', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Languages')),
('en', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'Languages')),
-- IdResolver
('type', (SELECT id FROM `inst_sections` WHERE `section_name` = 'IdResolver')),
('solrQueryField', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'IdResolver')),
('itemIdentifier', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'IdResolver')),
('stripPrefix', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'IdResolver'));

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`) VALUES
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'relative_path'), 'Aleph.common.ini'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'host'), '_API_HOST'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'dlfport'), '_DLF_API_URL'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'default_patron'), ''),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'send_language'), '1'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'hmac_key'), '_RANDOM_KEY'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'bib'), '_BIBBASE'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'useradm'), '_ADMBASE'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'admlib'), '_ADMBASE'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'wwwuser'), ''),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'wwwpasswd'), ''),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'available_statuses'), 'On Shelf,Na místě'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'payment_url'), ''),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'prolong_registration_url'), ''),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'prolong_registration_status'), ''),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'prefix'), '_SOURCE'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'source'), '_SOURCE'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'maxItemsParsed'), '15'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'default_required_date'), ''),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'barcode'), 'z304-address-5'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'fullname'), 'z304-address-1'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'street'), 'z304-address-2'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'city'), 'z304-address-3'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'zip'), 'z304-zip'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'email'), 'z304-email-address'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'phone'), 'z304-telephone-1'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'group'), 'z305-bor-status'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'expiration'), 'z305-expiry-date'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'timeout'), '10'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'sublibadm'), '_SUB_LIB_ADM'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'dlfbaseurl'), '_DLF_BASE_URL'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'type'), 'solr'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'solrQueryField'), 'aleph_adm_id_txt_mv'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'itemIdentifier'), 'adm_id'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'stripPrefix'), '1'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'cs'), 'cze'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!aleph'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'en'), 'eng'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'host'), '_API_HOST'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'tokenEndpoint'), '_API_TOKEN_ENDPOINT'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'source'), '_SOURCE'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'clientId'), '_CLIENT_ID'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'clientSecret'), '_CLIENT_SECRET'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'grantType'), 'client_credentials'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'defaultPickUpLocation'), '_MAIN_BRANCH'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'HMACKeys'), 'item_id:holdtype:level'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'defaultRequiredDate'), '0:0:1'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!koha'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'extraHoldFields'), 'requiredByDate:pickUpLocation'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'source'), '_SOURCE'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'url'), '_NCIP_URL'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'paymentUrl'), ''),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'agency'), '_SIGLA'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'timeout'), '10'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'hasUntrustedSSL'), '0'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'maximumItemsCount'), '10'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'username'), ''),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'password'), ''),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'hideHoldLinks'), '0'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'ils_type'), 'Arl,Clavius,DaVinci,Tritius,Verbis'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'pick_up_location'), '0'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'fromAgency'), 'CPK'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'consortium'), '0'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'disableRenewals'), '0'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'HMACKeys'), 'item_id:holdtype:level'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'defaultRequiredDate'), '0:0:1'),
((SELECT `id` FROM `inst_sources` WHERE `source` = '!ncip'), (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'extraHoldFields'), '0');

INSERT INTO inst_configs (`source_id`, `key_id`, `value`)
SELECT s.`id`, (SELECT k.`id` FROM `inst_keys` k WHERE k.key_name = 'disableRenewals') as key_id, 1
FROM `inst_sources` s WHERE s.`source` IN ('nlk', 'rkka');

INSERT INTO inst_configs (`source_id`, `key_id`, `value`)
SELECT s.`id`, (SELECT k.`id` FROM `inst_keys` k WHERE k.key_name = 'httpBasicAuth') as key_id, 1
FROM `inst_sources` s WHERE s.`source` IN ('nlk');

INSERT INTO inst_configs (`source_id`, `key_id`, `value`)
SELECT s.`id`, (SELECT k.`id` FROM `inst_keys` k WHERE k.key_name = 'pickupLocationsFromNCIP') as key_id, 1
FROM `inst_sources` s WHERE s.`source` IN ('mkp');

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
SELECT `id`, (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'prefix'), `source` FROM `inst_sources` WHERE `driver` = 'aleph';

-- We do not need logo setting (configurations are deleted using CASCADE on foreign key
DELETE FROM `inst_keys` WHERE (`key_name` = 'logo');

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
SELECT `inst_sources`.`id`, (SELECT `id` FROM `inst_keys` WHERE `key_name` = "transactionsHistory"), '1'
FROM `inst_sources`
         JOIN `inst_configs` ON `inst_sources`.`id` = `inst_configs`.`source_id`
WHERE `inst_sources`.`driver` = 'ncip' AND `inst_configs`.`key_id` = (SELECT `id` FROM `inst_keys` WHERE `key_name` = "ils_type") AND `inst_configs`.`value` = 'Verbis';

--
-- Add additional HTTP status codes for Tritius libraries
--
INSERT INTO inst_configs (`source_id`, `key_id`, `value`)
SELECT s.`id`, (SELECT k.`id` FROM `inst_keys` k WHERE k.key_name = 'otherAcceptedHttpStatusCodes') as key_id, '400'
FROM `inst_sources` s WHERE s.`driver` = 'ncip';

--
-- Aleph specific migrations:
--
SET @dlfbaseurl = (SELECT id FROM inst_keys WHERE key_name = 'dlfbaseurl'
  AND section_id = (SELECT id FROM inst_sections WHERE section_name = 'Catalog'));
SET @host = (SELECT id FROM inst_keys WHERE key_name = 'host'
  AND section_id = (SELECT id FROM inst_sections WHERE section_name = 'Catalog'));
SET @dlfport = (SELECT id FROM inst_keys WHERE key_name = 'dlfport'
  AND section_id = (SELECT id FROM inst_sections WHERE section_name = 'Catalog'));

-- host
UPDATE inst_configs ic
SET value = REPLACE(value, 'https://', '')
WHERE ic.source_id IN (SELECT id FROM inst_sources WHERE driver = 'aleph')
  AND ic.key_id = @host;

-- dlfbaseurl
INSERT INTO inst_configs(source_id, key_id, value, timestamp)
SELECT src.id source_id, @dlfbaseurl key_id, CONCAT('https://', ic1.value, ':', ic2.value, '/rest-dlf/') value, NOW() timestamp
FROM inst_sources src
  JOIN inst_configs ic1 ON ic1.source_id = src.id AND ic1.key_id = @host
  JOIN inst_configs ic2 ON ic2.source_id = src.id AND ic2.key_id = @dlfport
WHERE src.id IN (SELECT id FROM inst_sources WHERE driver = 'aleph') AND ic1.value NOT LIKE 'https://%'
  AND NOT EXISTS(SELECT 1 FROM inst_configs ic WHERE ic.key_id = @dlfbaseurl AND ic.source_id = src.id);

-- SVK HK does not run REST API on SSL
UPDATE inst_configs SET value = REPLACE(value, 'https://', 'http://')
WHERE source_id IN (
  SELECT id FROM inst_sources WHERE source
  IN ('svkhk')
) AND key_id = @dlfbaseurl;

-- delete empty wwwuser and wwwpasswd
DELETE FROM inst_configs
WHERE source_id IN (SELECT id FROM inst_sources WHERE driver = 'aleph')
  AND TRIM(value) = ''
  AND key_id IN (
    SELECT id
    FROM inst_keys
    WHERE key_name IN ('wwwuser', 'wwwpasswd')
      AND section_id = (SELECT id FROM inst_sections WHERE section_name = 'Catalog')
);

-- Transaction history for Aleph
INSERT INTO `inst_sections` (`section_name`) VALUES('TransactionHistory');
INSERT INTO `inst_keys` (`key_name`, `section_id`) VALUES ('enabled', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'TransactionHistory'));

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
SELECT `id`, (SELECT `id` FROM `inst_keys` WHERE `key_name` = 'enabled'), 'true'
FROM `inst_sources` WHERE `driver` = 'aleph';

-- IdResolver settings
DELETE ic
FROM inst_configs ic
    JOIN inst_sources isrc ON ic.source_id = isrc.id
    JOIN inst_keys ik ON ic.key_id = ik.id
    JOIN inst_sections isec ON ik.section_id = isec.id
    WHERE isec.section_name = 'IdResolver' AND isrc.driver IN ('koha');

DELETE FROM inst_keys WHERE key_name IN ('prefix', 'type', 'stripPrefix');
INSERT INTO `inst_keys` (`key_name`, `section_id`) VALUES
('solrQueryFieldPrefix', (SELECT `id` FROM `inst_sections` WHERE `section_name` = 'IdResolver'));

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
SELECT `inst_sources`.`id`, (SELECT `id` FROM `inst_keys` WHERE `key_name` = "solrQueryFieldPrefix"), `inst_configs`.`value`
FROM `inst_configs`
         JOIN `inst_sources` ON `inst_configs`.`source_id` = `inst_sources`.`id`
         JOIN `inst_keys` ON inst_configs.`key_id` = `inst_keys`.`id`
WHERE inst_keys.key_name = "admlib";

UPDATE `inst_configs` SET `value` = 'stk50'
WHERE `key_id` = (SELECT `id` FROM `inst_keys` WHERE `key_name` = "solrQueryFieldPrefix")
AND `value` = 'STK50'
AND `source_id` = (SELECT `source_id` FROM `inst_sources` WHERE `source` = 'ntk');

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
SELECT `inst_sources`.`id`, (SELECT `id` FROM `inst_keys` WHERE `key_name` = "solrQueryField"), 'barcodes'
FROM `inst_sources`
JOIN `inst_configs` ON `inst_sources`.`id` = `inst_configs`.`source_id`
WHERE `inst_sources`.`driver` = 'ncip' AND `inst_configs`.`key_id` = (SELECT `id` FROM `inst_keys` WHERE `key_name` = "ils_type") AND `inst_configs`.`value` = 'Tritius';

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
SELECT `inst_sources`.`id`, (SELECT `id` FROM `inst_keys` WHERE `key_name` = "itemIdentifier"), 'item_id'
FROM `inst_sources`
JOIN `inst_configs` ON `inst_sources`.`id` = `inst_configs`.`source_id`
WHERE `inst_sources`.`driver` = 'ncip' AND `inst_configs`.`key_id` = (SELECT `id` FROM `inst_keys` WHERE `key_name` = "ils_type") AND `inst_configs`.`value` = 'Tritius';

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
SELECT `inst_sources`.`id`, (SELECT `id` FROM `inst_keys` WHERE `key_name` = "solrQueryField"), 'barcodes'
FROM `inst_sources`
         JOIN `inst_configs` ON `inst_sources`.`id` = `inst_configs`.`source_id`
WHERE `inst_sources`.`driver` = 'ncip' AND `inst_configs`.`key_id` = (SELECT `id` FROM `inst_keys` WHERE `key_name` = "ils_type") AND `inst_configs`.`value` = 'Arl';

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
SELECT `inst_sources`.`id`, (SELECT `id` FROM `inst_keys` WHERE `key_name` = "itemIdentifier"), 'item_id'
FROM `inst_sources`
         JOIN `inst_configs` ON `inst_sources`.`id` = `inst_configs`.`source_id`
WHERE `inst_sources`.`driver` = 'ncip' AND `inst_configs`.`key_id` = (SELECT `id` FROM `inst_keys` WHERE `key_name` = "ils_type") AND `inst_configs`.`value` = 'Arl';

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
SELECT `inst_sources`.`id`, (SELECT `id` FROM `inst_keys` WHERE `key_name` = "solrQueryField"), 'barcodes'
FROM `inst_sources`
         JOIN `inst_configs` ON `inst_sources`.`id` = `inst_configs`.`source_id`
WHERE `inst_sources`.`driver` = 'ncip' AND `inst_configs`.`key_id` = (SELECT `id` FROM `inst_keys` WHERE `key_name` = "ils_type") AND `inst_configs`.`value` = 'Clavius';

INSERT INTO `inst_configs` (`source_id`, `key_id`, `value`)
SELECT `inst_sources`.`id`, (SELECT `id` FROM `inst_keys` WHERE `key_name` = "itemIdentifier"), 'item_id'
FROM `inst_sources`
         JOIN `inst_configs` ON `inst_sources`.`id` = `inst_configs`.`source_id`
WHERE `inst_sources`.`driver` = 'ncip' AND `inst_configs`.`key_id` = (SELECT `id` FROM `inst_keys` WHERE `key_name` = "ils_type") AND `inst_configs`.`value` = 'Clavius';

-- NoILS configuration
INSERT INTO inst_keys(key_name, section_id)
VALUES('mode', (SELECT id FROM inst_sections WHERE section_name = 'settings'));
SELECT @mode_id := LAST_INSERT_ID();
INSERT INTO inst_configs(source_id, key_id, value, timestamp)
SELECT s.id, @mode_id, 'ils-none', NOW()
FROM inst_sources s WHERE s.driver = 'noils';

INSERT INTO inst_keys(key_name, section_id)
VALUES('hideLogin', (SELECT id FROM inst_sections WHERE section_name = 'settings'));
SELECT @hide_login_id := LAST_INSERT_ID();
INSERT INTO inst_configs(source_id, key_id, value, timestamp)
SELECT s.id, @hide_login_id, 'true', NOW()
FROM inst_sources s WHERE s.driver = 'noils';
