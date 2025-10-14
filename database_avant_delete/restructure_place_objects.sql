-- Restructuration de la table place_objects pour un système plus cohérent

-- Créer une table temporaire avec la nouvelle structure
CREATE TABLE place_objects_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    
    -- Informations de base de l'objet
    display_name VARCHAR(255) NOT NULL COMMENT 'Nom d\'affichage de l\'objet',
    object_type ENUM('poison', 'weapon', 'armor', 'bourse', 'letter') NOT NULL COMMENT 'Type principal de l\'objet',
    type_precis VARCHAR(100) NULL COMMENT 'Type précis (nom du poison, arme, armure)',
    description TEXT NULL COMMENT 'Description générale de l\'objet',
    
    -- État de l'objet
    is_identified BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Si l\'objet est identifié par les joueurs',
    is_visible BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Si l\'objet est visible des joueurs',
    is_equipped BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Si l\'objet est équipé (pour armes/armures)',
    
    -- Position sur la carte
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    is_on_map BOOLEAN DEFAULT FALSE,
    
    -- Propriétaire de l'objet
    owner_type ENUM('place', 'player', 'npc', 'monster') NOT NULL DEFAULT 'place' COMMENT 'Type de propriétaire',
    owner_id INT NULL COMMENT 'ID du propriétaire (place_id, player_id, npc_id, monster_id)',
    
    -- Données spécifiques selon le type
    poison_id INT NULL COMMENT 'ID du poison (référence vers poisons.id)',
    weapon_id INT NULL COMMENT 'ID de l\'arme (référence vers weapons.id)',
    armor_id INT NULL COMMENT 'ID de l\'armure (référence vers armor.id)',
    
    -- Données pour les bourses
    gold_coins INT NOT NULL DEFAULT 0,
    silver_coins INT NOT NULL DEFAULT 0,
    copper_coins INT NOT NULL DEFAULT 0,
    
    -- Données pour les lettres
    letter_content TEXT NULL,
    is_sealed BOOLEAN DEFAULT FALSE,
    
    -- Métadonnées
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Clés étrangères
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (poison_id) REFERENCES poisons(id) ON DELETE SET NULL,
    FOREIGN KEY (weapon_id) REFERENCES weapons(id) ON DELETE SET NULL,
    FOREIGN KEY (armor_id) REFERENCES armor(id) ON DELETE SET NULL,
    
    -- Index
    INDEX idx_place_id (place_id),
    INDEX idx_owner (owner_type, owner_id),
    INDEX idx_object_type (object_type),
    INDEX idx_visible (is_visible),
    INDEX idx_identified (is_identified)
);

-- Migrer les données existantes
INSERT INTO place_objects_new (
    id, place_id, display_name, object_type, type_precis, description,
    is_identified, is_visible, is_equipped, position_x, position_y, is_on_map,
    owner_type, owner_id, poison_id, weapon_id, armor_id,
    gold_coins, silver_coins, copper_coins, letter_content, is_sealed,
    created_at, updated_at
)
SELECT 
    id, place_id, 
    CASE 
        WHEN object_type = 'coins' THEN 'Bourse'
        ELSE name 
    END as display_name,
    CASE 
        WHEN object_type = 'coins' THEN 'bourse'
        WHEN object_type = 'magical_item' THEN 'weapon' -- À ajuster selon les cas
        ELSE object_type 
    END as object_type,
    CASE 
        WHEN object_type = 'poison' AND item_name IS NOT NULL THEN item_name
        WHEN object_type = 'weapon' AND item_name IS NOT NULL THEN item_name
        WHEN object_type = 'armor' AND item_name IS NOT NULL THEN item_name
        ELSE NULL 
    END as type_precis,
    description,
    is_identified, is_visible, FALSE as is_equipped, position_x, position_y, is_on_map,
    CASE 
        WHEN owner_type = 'none' THEN 'place'
        ELSE owner_type 
    END as owner_type,
    owner_id,
    CASE WHEN object_type = 'poison' THEN item_id ELSE NULL END as poison_id,
    CASE WHEN object_type = 'weapon' THEN item_id ELSE NULL END as weapon_id,
    CASE WHEN object_type = 'armor' THEN item_id ELSE NULL END as armor_id,
    gold_coins, silver_coins, copper_coins, letter_content, is_sealed,
    created_at, updated_at
FROM place_objects;

-- Remplacer l'ancienne table
DROP TABLE IF EXISTS place_objects_backup;
RENAME TABLE place_objects TO place_objects_backup;
RENAME TABLE place_objects_new TO place_objects;
