-- Script pour remplacer la table starting_equipment par une nouvelle version
-- avec les colonnes spécifiées

-- 1. Supprimer l'ancienne table
DROP TABLE IF EXISTS starting_equipment;

-- 2. Créer la nouvelle table starting_equipment avec les nouvelles colonnes
CREATE TABLE starting_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    src VARCHAR(20) NOT NULL COMMENT 'Source d\'origine: class, background, race',
    src_id INT NOT NULL COMMENT 'ID de la source d\'origine',
    type VARCHAR(20) NOT NULL COMMENT 'Type d\'équipement: Outils, Armure, Bouclier, Arme, Accessoire, Sac',
    type_id INT COMMENT 'ID de l\'équipement précis dans la table de description lié au type',
    type_filter VARCHAR(50) COMMENT 'Si c\'est une alternative à choisir dans une liste du type défini',
    no_choix INT COMMENT 'Le numéro du choix',
    option_letter CHAR(1) COMMENT 'La lettre d\'option: a, b, c',
    type_choix ENUM('obligatoire', 'à_choisir') DEFAULT 'obligatoire' COMMENT 'Type de choix: obligatoire ou à choisir',
    nb INT DEFAULT 1 COMMENT 'Le nombre d\'objet',
    groupe_id INT COMMENT 'ID de groupe pour les items venant en groupe',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_src_src_id (src, src_id),
    INDEX idx_type (type),
    INDEX idx_groupe_id (groupe_id),
    INDEX idx_option_letter (option_letter),
    INDEX idx_no_choix (no_choix)
);

-- Commentaires sur les colonnes
ALTER TABLE starting_equipment 
    MODIFY COLUMN src VARCHAR(20) NOT NULL COMMENT 'Source d\'origine: class, background, race',
    MODIFY COLUMN src_id INT NOT NULL COMMENT 'ID de la source d\'origine',
    MODIFY COLUMN type VARCHAR(20) NOT NULL COMMENT 'Type d\'équipement: Outils, Armure, Bouclier, Arme, Accessoire, Sac',
    MODIFY COLUMN type_id INT COMMENT 'ID de l\'équipement précis dans la table de description lié au type',
    MODIFY COLUMN type_filter VARCHAR(50) COMMENT 'Si c\'est une alternative à choisir dans une liste du type défini',
    MODIFY COLUMN no_choix INT COMMENT 'Le numéro du choix',
    MODIFY COLUMN option_letter CHAR(1) COMMENT 'La lettre d\'option: a, b, c',
    MODIFY COLUMN type_choix ENUM('obligatoire', 'à_choisir') DEFAULT 'obligatoire' COMMENT 'Type de choix: obligatoire ou à choisir',
    MODIFY COLUMN nb INT DEFAULT 1 COMMENT 'Le nombre d\'objet',
    MODIFY COLUMN groupe_id INT COMMENT 'ID de groupe pour les items venant en groupe';
