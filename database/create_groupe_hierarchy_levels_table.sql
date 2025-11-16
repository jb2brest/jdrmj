-- =====================================================
-- Table pour stocker les configurations des niveaux hiérarchiques des groupes
-- =====================================================
-- 
-- Permet de définir un titre et une description pour chaque niveau hiérarchique
-- d'un groupe (ex: "Dirigeant", "Lieutenant", "Membre", etc.)
--
-- =====================================================

CREATE TABLE IF NOT EXISTS groupe_hierarchy_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    groupe_id INT NOT NULL,
    level_number INT NOT NULL COMMENT 'Numéro du niveau (1, 2, 3, etc.)',
    title VARCHAR(255) NULL COMMENT 'Titre du niveau (ex: "Dirigeant", "Lieutenant")',
    description TEXT NULL COMMENT 'Description du niveau',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (groupe_id) REFERENCES groupes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_groupe_level (groupe_id, level_number),
    INDEX idx_groupe_id (groupe_id),
    INDEX idx_level_number (level_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

