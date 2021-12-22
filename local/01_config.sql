SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

-- Config files
DROP TABLE IF EXISTS `config_files`;
CREATE TABLE `config_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `file_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Název souboru',
    PRIMARY KEY (`id`),
    KEY `name` (`file_name`(190))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Konfigurační soubory';

INSERT INTO `config_files` (`id`, `file_name`) VALUES
    (1, 'searches'),
    (2, 'content'),
    (3, 'citation');

-- Config sections
DROP TABLE IF EXISTS `config_sections`;
CREATE TABLE `config_sections` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `section_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Název sekce',
    PRIMARY KEY (`id`),
    KEY `section_name` (`section_name`(190))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sekce konfigurace';

INSERT INTO `config_sections` (`id`, `section_name`) VALUES
    (1, 'HomePage'),
    (2, 'Inspiration'),
    (3, 'DocumentTypesContentBlock'),
    (4, 'Footer'),
    (5, 'Citation');

-- Cog items
DROP TABLE IF EXISTS `config_items`;
CREATE TABLE `config_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Název položky',
    `type` enum('string','array') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string' COMMENT 'Typ',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Konfigurační položky';

INSERT INTO `config_items` (`id`, `name`, `type`) VALUES
    (1, 'content', 'array'),
    (2, 'item', 'array'),
    (3, 'content_block', 'array'),
    (4, 'column1', 'array'),
    (5, 'column2', 'array'),
    (6, 'column3', 'array'),
    (7, 'column4', 'array'),
    (8, 'default_citation_style', 'string'),
    (9, 'citation_local_domain', 'string'),
    (10, 'citation_styles', 'array');

-- Config values
DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `file_id` int(11) NOT NULL COMMENT 'Soubor',
    `section_id` int(11) NOT NULL COMMENT 'Sekce',
    `item_id` int(11) NOT NULL COMMENT 'Položka (klíč)',
    `array_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Klíč pole (nepovinné)',
    `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hodnota',
    `order` int(11) NOT NULL COMMENT 'Pořadí',
    `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Aktivní?',
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

INSERT INTO `config` (`id`, `file_id`, `section_id`, `item_id`, `array_key`, `value`, `order`, `active`) VALUES
    (1, 2, 2, 3, NULL, 'Inspiration:magnesia_litera', 10, 0),
    (2, 2, 2, 6, NULL, 'Inspiration:magnesia_litera', 20, 1),
    (3, 2, 1, 5, NULL, 'Inspiration:magnesia_litera', 10, 1),
    (4, 1, 1, 4, NULL, 'Inspiration:magnesia_litera', 20, 1),
    (5, 2, 2, 3, NULL, 'Inspiration:nejpujcovanejsi_2015:5', 30, 1),
    (7, 2, 1, 1, NULL, 'Inspiration:magnesia_litera', 40, 0),
    (8, 2, 1, 2, NULL, 'doctypes_widget_norms;doctypes_widget_norms_description;pr-format-norms;0/NORMS/', 10, 1),
    (9, 1, 3, 2, NULL, 'doctypes_widget_maps;doctypes_widget_maps_description;pr-format-maps;0/MAPS/', 20, 1),
    (10, 1, 3, 2, NULL, 'doctypes_widget_legislative_laws;doctypes_widget_legislative_laws_description;pr-format-legislative;0/LEGISLATIVE/', 30, 1),
    (11, 1, 3, 2, NULL, 'doctypes_widget_authorities;doctypes_widget_authorities_description;pr-format-otherperson;1/OTHER/PERSON/', 40, 1),
    (12, 1, 3, 2, NULL, 'doctypes_widget_patents;doctypes_widget_patents_description;pr-format-patents;0/PATENTS/', 50, 1),
    (13, 1, 3, 2, NULL, 'doctypes_widget_articles;doctypes_widget_articles_description;pr-format-articles;0/ARTICLES/', 60, 1),
    (14, 1, 3, 2, NULL, 'doctypes_widget_musical_scores;doctypes_widget_musical_scores_description;pr-format-musicalscores;0/MUSICAL_SCORES/', 70, 1),
    (15, 2, 2, 3, NULL, 'Inspiration:magnesia_litera:5', 40, 1),
    (16, 2, 2, 3, NULL, 'Inspiration:nobelova_cena_za_literaturu:5', 50, 1),
    (17, 2, 2, 3, NULL, 'Inspiration:znate_z_kina_a_televize:5', 60, 1),
    (19, 2, 2, 3, NULL, 'Inspiration:audiobooks:5', 80, 1),
    (20, 2, 2, 3, NULL, 'Inspiration:online_dostupna_klasicka_literatura:5', 90, 1),
    (21, 2, 2, 3, NULL, 'Inspiration:eknihy_ke_stazeni:5', 100, 1),
    (23, 2, 2, 3, NULL, 'DocumentTypes:DocumentTypesContentBlock', 120, 1),
    (24, 2, 4, 4, NULL, '/Content/o-portalu', 10, 1),
    (25, 2, 1, 1, NULL, 'Inspiration:zdravavyziva:6', 50, 1),
    (27, 3, 5, 9, NULL, 'knihovny.cz', 0, 1),
    (38, 1, 1, 1, NULL, 'TemplateBased:header-panel', 10, 1),
    (39, 1, 1, 1, NULL, 'Inspiration:nejpujcovanejsi_2015:5', 20, 1),
    (40, 1, 1, 1, NULL, 'Inspiration:eknihy_ke_stazeni:5', 30, 1),
    (41, 1, 1, 1, NULL, 'DocumentTypes:DocumentTypesContentBlock', 15, 1);
