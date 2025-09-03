-- Ajout de la table d'équipement pour gérer les objets magiques attribués
-- Ce script doit être exécuté après la création de la base de données principale

USE dnd_characters;

-- Table pour gérer l'équipement des personnages
CREATE TABLE IF NOT EXISTS character_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_type VARCHAR(100),
    item_description TEXT,
    item_source VARCHAR(100),
    quantity INT DEFAULT 1,
    equipped BOOLEAN DEFAULT FALSE,
    notes TEXT,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    obtained_from VARCHAR(100) DEFAULT 'Attribution MJ',
    
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    INDEX idx_character_id (character_id),
    INDEX idx_item_name (item_name)
);

-- Table pour gérer l'équipement des PNJ (si pas de personnage associé)
CREATE TABLE IF NOT EXISTS npc_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    npc_id INT NOT NULL,
    scene_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_type VARCHAR(100),
    item_description TEXT,
    item_source VARCHAR(100),
    quantity INT DEFAULT 1,
    equipped BOOLEAN DEFAULT FALSE,
    notes TEXT,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    obtained_from VARCHAR(100) DEFAULT 'Attribution MJ',
    
    INDEX idx_npc_id (npc_id),
    INDEX idx_scene_id (scene_id),
    INDEX idx_item_name (item_name)
);

-- Table pour gérer l'équipement des monstres
CREATE TABLE IF NOT EXISTS monster_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    monster_id INT NOT NULL,
    scene_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_type VARCHAR(100),
    item_description TEXT,
    item_source VARCHAR(100),
    quantity INT DEFAULT 1,
    equipped BOOLEAN DEFAULT FALSE,
    notes TEXT,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    obtained_from VARCHAR(100) DEFAULT 'Attribution MJ',
    
    INDEX idx_monster_id (monster_id),
    INDEX idx_scene_id (scene_id),
    INDEX idx_item_name (item_name)
);

-- Ajout d'un champ pour l'ID de l'objet magique (pour référence)
ALTER TABLE character_equipment ADD COLUMN magical_item_id VARCHAR(50) AFTER item_name;
ALTER TABLE npc_equipment ADD COLUMN magical_item_id VARCHAR(50) AFTER item_name;
ALTER TABLE monster_equipment ADD COLUMN magical_item_id VARCHAR(50) AFTER item_name;

-- Index pour les IDs d'objets magiques
ALTER TABLE character_equipment ADD INDEX idx_magical_item_id (magical_item_id);
ALTER TABLE npc_equipment ADD INDEX idx_magical_item_id (magical_item_id);
ALTER TABLE monster_equipment ADD INDEX idx_magical_item_id (magical_item_id);
