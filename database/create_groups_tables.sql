-- Script de création des tables pour la gestion des groupes
-- Exécuter ce script pour créer les tables nécessaires

-- Table des groupes
CREATE TABLE IF NOT EXISTS `groupes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `headquarters_place_id` int(11) NOT NULL,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_headquarters_place_id` (`headquarters_place_id`),
    KEY `idx_created_by` (`created_by`),
    CONSTRAINT `fk_groupes_headquarters_place` FOREIGN KEY (`headquarters_place_id`) REFERENCES `places` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_groupes_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des membres de groupes
CREATE TABLE IF NOT EXISTS `groupe_membres` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `groupe_id` int(11) NOT NULL,
    `member_id` int(11) NOT NULL,
    `member_type` enum('pnj','pj','monster') NOT NULL,
    `hierarchy_level` int(11) NOT NULL DEFAULT 2,
    `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_member_in_group` (`groupe_id`, `member_id`, `member_type`),
    KEY `idx_groupe_id` (`groupe_id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_member_type` (`member_type`),
    KEY `idx_hierarchy_level` (`hierarchy_level`),
    CONSTRAINT `fk_groupe_membres_groupe` FOREIGN KEY (`groupe_id`) REFERENCES `groupes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index pour optimiser les requêtes
CREATE INDEX IF NOT EXISTS `idx_groupes_name` ON `groupes` (`name`);
CREATE INDEX IF NOT EXISTS `idx_groupe_membres_hierarchy` ON `groupe_membres` (`groupe_id`, `hierarchy_level`);

-- Commentaires sur les tables
ALTER TABLE `groupes` COMMENT = 'Table des groupes de PNJ, PJ et Monstres';
ALTER TABLE `groupe_membres` COMMENT = 'Table des membres des groupes avec hiérarchie';

