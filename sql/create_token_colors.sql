-- Script SQL pour créer la table token_colors
-- Cette table stocke les couleurs personnalisées des pions dans view_place

CREATE TABLE IF NOT EXISTS `token_colors` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `place_id` INT(11) NOT NULL,
  `token_type` ENUM('player', 'npc', 'monster', 'object') NOT NULL,
  `entity_id` INT(11) NOT NULL,
  `border_color` VARCHAR(7) NOT NULL DEFAULT '#007bff',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`place_id`, `token_type`, `entity_id`),
  FOREIGN KEY (`place_id`) REFERENCES `places` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index pour améliorer les performances de recherche
CREATE INDEX `idx_place_type` ON `token_colors` (`place_id`, `token_type`);
