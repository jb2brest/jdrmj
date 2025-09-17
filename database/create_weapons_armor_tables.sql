-- Création des tables pour les armes et armures

-- Table des armes
CREATE TABLE IF NOT EXISTS weapons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    damage VARCHAR(50) NOT NULL,
    weight VARCHAR(20) NOT NULL,
    price VARCHAR(20) NOT NULL,
    properties TEXT,
    hands INT NOT NULL DEFAULT 1,
    type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des armures
CREATE TABLE IF NOT EXISTS armor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    ac_formula VARCHAR(50) NOT NULL,
    strength_requirement VARCHAR(20),
    stealth_disadvantage VARCHAR(20),
    weight VARCHAR(20) NOT NULL,
    price VARCHAR(20) NOT NULL,
    type VARCHAR(100) NOT NULL,
    don_time VARCHAR(20) NOT NULL,
    doff_time VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table pour l'équipement des personnages
CREATE TABLE IF NOT EXISTS character_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    item_type ENUM('weapon', 'armor', 'shield', 'other') NOT NULL,
    is_equipped BOOLEAN DEFAULT FALSE,
    equipped_slot ENUM('main_hand', 'off_hand', 'armor', 'shield') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Index pour optimiser les requêtes
CREATE INDEX idx_character_equipment_character_id ON character_equipment(character_id);
CREATE INDEX idx_character_equipment_type ON character_equipment(item_type);
CREATE INDEX idx_character_equipment_equipped ON character_equipment(is_equipped);
