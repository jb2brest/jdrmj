-- Refactorisation des tables starting_equipment selon les nouvelles spécifications
-- La table starting_equipment reste la source, les autres tables sont restructurées

-- 1. Supprimer les anciennes tables starting_equipment_options et starting_equipment_choix
DROP TABLE IF EXISTS starting_equipment_options;
DROP TABLE IF EXISTS starting_equipment_choix;

-- 2. Créer la nouvelle table starting_equipment_choix
-- Structure simplifiée selon les nouvelles spécifications
CREATE TABLE starting_equipment_choix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    src ENUM('class', 'background') NOT NULL COMMENT 'Source: class ou background',
    src_id INT NOT NULL COMMENT 'ID de la classe ou du background concerné',
    no_choix INT NOT NULL COMMENT 'Le numéro du choix',
    option_letter CHAR(1) COMMENT 'La lettre d\'option du package',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_src_src_id (src, src_id),
    INDEX idx_no_choix (no_choix),
    INDEX idx_option_letter (option_letter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Créer la nouvelle table starting_equipment_options
-- Structure avec relation directe vers starting_equipment_choix
CREATE TABLE starting_equipment_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    starting_equipment_choix_id INT NOT NULL COMMENT 'ID du choix dont fait partie l\'option',
    src ENUM('class', 'background') NOT NULL COMMENT 'Source: class ou background',
    src_id INT NOT NULL COMMENT 'ID de la classe ou du background concerné',
    type ENUM('armor', 'bouclier', 'instrument', 'nourriture', 'outils', 'sac', 'weapon') NOT NULL COMMENT 'Type d\'équipement',
    type_id INT COMMENT 'ID de l\'équipement dans la table correspondant au type',
    type_filter VARCHAR(100) COMMENT 'Filtre pour sélectionner des armes dans une liste (ex: Armes de guerre de corps à corps, Armes courantes à distance, etc.)',
    nb INT DEFAULT 1 COMMENT 'Le nombre d\'item',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (starting_equipment_choix_id) REFERENCES starting_equipment_choix(id) ON DELETE CASCADE,
    INDEX idx_starting_equipment_choix_id (starting_equipment_choix_id),
    INDEX idx_src_src_id (src, src_id),
    INDEX idx_type (type),
    INDEX idx_type_id (type_id),
    INDEX idx_type_filter (type_filter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Ajouter des commentaires sur les colonnes pour clarifier la structure
ALTER TABLE starting_equipment_choix 
    MODIFY COLUMN src ENUM('class', 'background') NOT NULL COMMENT 'Source: class ou background',
    MODIFY COLUMN src_id INT NOT NULL COMMENT 'ID de la classe ou du background concerné',
    MODIFY COLUMN no_choix INT NOT NULL COMMENT 'Le numéro du choix',
    MODIFY COLUMN option_letter CHAR(1) COMMENT 'La lettre d\'option du package';

ALTER TABLE starting_equipment_options 
    MODIFY COLUMN starting_equipment_choix_id INT NOT NULL COMMENT 'ID du choix dont fait partie l\'option',
    MODIFY COLUMN src ENUM('class', 'background') NOT NULL COMMENT 'Source: class ou background',
    MODIFY COLUMN src_id INT NOT NULL COMMENT 'ID de la classe ou du background concerné',
    MODIFY COLUMN type ENUM('armor', 'bouclier', 'instrument', 'nourriture', 'outils', 'sac', 'weapon') NOT NULL COMMENT 'Type d\'équipement',
    MODIFY COLUMN type_id INT COMMENT 'ID de l\'équipement dans la table correspondant au type',
    MODIFY COLUMN type_filter VARCHAR(100) COMMENT 'Filtre pour sélectionner des armes dans une liste (ex: Armes de guerre de corps à corps, Armes courantes à distance, etc.)',
    MODIFY COLUMN nb INT DEFAULT 1 COMMENT 'Le nombre d\'item';
