-- Création de la table starting_equipment pour gérer l'équipement de départ
-- Cette table remplace les champs starting_equipment dans classes et equipment dans backgrounds

CREATE TABLE IF NOT EXISTS starting_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    src VARCHAR(20) NOT NULL COMMENT 'Source d\'origine: class, background, race',
    src_id INT NOT NULL COMMENT 'ID de la source d\'origine',
    type VARCHAR(20) NOT NULL COMMENT 'Type d\'équipement: Outils, Armure, Bouclier, Arme, Accessoire, Sac',
    type_id INT COMMENT 'ID de l\'équipement précis dans la table de description lié au type',
    option_indice CHAR(1) COMMENT 'Indice d\'option: a, b, c',
    groupe_id INT COMMENT 'ID de groupe pour les items venant en groupe',
    type_choix ENUM('obligatoire', 'à_choisir') DEFAULT 'obligatoire' COMMENT 'Type de choix: obligatoire ou à choisir',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_src_src_id (src, src_id),
    INDEX idx_type (type),
    INDEX idx_groupe_id (groupe_id),
    INDEX idx_option_indice (option_indice)
);

-- Commentaires sur les colonnes
ALTER TABLE starting_equipment 
    MODIFY COLUMN src VARCHAR(20) NOT NULL COMMENT 'Source d\'origine: class, background, race',
    MODIFY COLUMN src_id INT NOT NULL COMMENT 'ID de la source d\'origine',
    MODIFY COLUMN type VARCHAR(20) NOT NULL COMMENT 'Type d\'équipement: Outils, Armure, Bouclier, Arme, Accessoire, Sac',
    MODIFY COLUMN type_id INT COMMENT 'ID de l\'équipement précis dans la table de description lié au type',
    MODIFY COLUMN option_indice CHAR(1) COMMENT 'Indice d\'option: a, b, c',
    MODIFY COLUMN groupe_id INT COMMENT 'ID de groupe pour les items venant en groupe',
    MODIFY COLUMN type_choix ENUM('obligatoire', 'à_choisir') DEFAULT 'obligatoire' COMMENT 'Type de choix: obligatoire ou à choisir';
