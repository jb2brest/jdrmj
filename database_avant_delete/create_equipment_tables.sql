-- Création des tables d'équipement pour le système D&D
-- Ces tables permettent de gérer l'équipement des personnages, PNJ et monstres

-- Table character_equipment
CREATE TABLE IF NOT EXISTS character_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    magical_item_id VARCHAR(50),
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
    INDEX idx_item_name (item_name),
    INDEX idx_magical_item_id (magical_item_id)
);

-- Table npc_equipment
CREATE TABLE IF NOT EXISTS npc_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    npc_id INT NOT NULL,
    scene_id INT NOT NULL,
    magical_item_id VARCHAR(50),
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
    INDEX idx_item_name (item_name),
    INDEX idx_magical_item_id (magical_item_id)
);

-- Table monster_equipment
CREATE TABLE IF NOT EXISTS monster_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    monster_id INT NOT NULL,
    scene_id INT NOT NULL,
    magical_item_id VARCHAR(50),
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
    INDEX idx_item_name (item_name),
    INDEX idx_magical_item_id (magical_item_id)
);

-- Table poisons (si elle n'existe pas déjà)
CREATE TABLE IF NOT EXISTS poisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50),
    description TEXT,
    effect TEXT,
    cost VARCHAR(50),
    source VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_type (type)
);

-- Table weapons (si elle n'existe pas déjà)
CREATE TABLE IF NOT EXISTS weapons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50),
    damage VARCHAR(20),
    damage_type VARCHAR(50),
    properties TEXT,
    cost VARCHAR(50),
    weight VARCHAR(20),
    source VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_type (type)
);

-- Table armors (si elle n'existe pas déjà)
CREATE TABLE IF NOT EXISTS armors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50),
    armor_class VARCHAR(20),
    properties TEXT,
    cost VARCHAR(50),
    weight VARCHAR(20),
    source VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_type (type)
);

-- Table magical_items (si elle n'existe pas déjà)
CREATE TABLE IF NOT EXISTS magical_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50),
    rarity VARCHAR(50),
    description TEXT,
    properties TEXT,
    cost VARCHAR(50),
    source VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_type (type),
    INDEX idx_rarity (rarity)
);
