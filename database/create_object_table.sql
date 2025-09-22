-- Création de la table Object pour gérer les objets d'équipement
-- Cette table contient les objets de type sac, outils, nourriture

CREATE TABLE IF NOT EXISTS Object (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('sac', 'outils', 'nourriture') NOT NULL COMMENT 'Type d\'objet: sac, outils, nourriture',
    nom VARCHAR(100) NOT NULL COMMENT 'Nom de l\'objet',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type (type),
    INDEX idx_nom (nom),
    UNIQUE KEY unique_nom_type (nom, type)
);

-- Commentaires sur les colonnes
ALTER TABLE Object 
    MODIFY COLUMN type ENUM('sac', 'outils', 'nourriture') NOT NULL COMMENT 'Type d\'objet: sac, outils, nourriture',
    MODIFY COLUMN nom VARCHAR(100) NOT NULL COMMENT 'Nom de l\'objet';
