-- Table PT_items pour stocker l'équipement de départ des personnages temporaires
-- Structure similaire à la table items mais pour les personnages en cours de création

CREATE TABLE IF NOT EXISTS PT_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pt_character_id INT NOT NULL,
    
    -- Informations de base de l'objet
    display_name VARCHAR(255) NOT NULL COMMENT 'Nom d\'affichage de l\'objet',
    object_type VARCHAR(100) NOT NULL COMMENT 'Type d\'objet (weapon, armor, shield, etc.)',
    type_precis VARCHAR(100) NULL COMMENT 'Type précis',
    description TEXT NULL COMMENT 'Description de l\'objet',
    
    -- État de l'objet
    is_identified BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Les objets de départ sont identifiés',
    is_visible BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Non visible (dans l\'inventaire)',
    is_equipped BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Si l\'objet est équipé',
    
    -- Position (non utilisée pour les PT_items mais conservée pour compatibilité)
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    is_on_map BOOLEAN DEFAULT FALSE,
    
    -- Références aux tables spécialisées
    weapon_id INT NULL COMMENT 'ID de l\'arme (référence vers weapons.id)',
    armor_id INT NULL COMMENT 'ID de l\'armure (référence vers armor.id)',
    shield_id INT NULL COMMENT 'ID du bouclier (référence vers shields.id)',
    poison_id INT NULL COMMENT 'ID du poison (référence vers poisons.id)',
    magical_item_id INT NULL COMMENT 'ID de l\'objet magique (référence vers magical_items.id)',
    
    -- Données pour les bourses
    gold_coins INT NOT NULL DEFAULT 0,
    silver_coins INT NOT NULL DEFAULT 0,
    copper_coins INT NOT NULL DEFAULT 0,
    
    -- Données pour les lettres
    letter_content TEXT NULL,
    is_sealed BOOLEAN DEFAULT FALSE,
    
    -- Quantité et slot d'équipement
    quantity INT NOT NULL DEFAULT 1 COMMENT 'Quantité de l\'objet',
    equipped_slot VARCHAR(50) NULL COMMENT 'Slot d\'équipement si équipé',
    
    -- Métadonnées
    item_source VARCHAR(100) NULL COMMENT 'Source de l\'objet (Équipement de départ, etc.)',
    notes TEXT NULL COMMENT 'Notes supplémentaires',
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date d\'obtention',
    obtained_from VARCHAR(255) NULL COMMENT 'Source d\'obtention',
    
    -- Informations de choix pour restauration
    no_choix INT NULL COMMENT 'Numéro de choix (no_choix) depuis starting_equipment_choix',
    option_letter CHAR(1) NULL COMMENT 'Lettre d\'option (a, b, c) depuis starting_equipment_choix',
    starting_equipment_choix_id INT NULL COMMENT 'ID du choix dans starting_equipment_choix',
    src VARCHAR(20) NULL COMMENT 'Source de l\'équipement: class, background',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pt_character_id) REFERENCES PT_characters(id) ON DELETE CASCADE,
    INDEX idx_pt_character_id (pt_character_id),
    INDEX idx_object_type (object_type),
    INDEX idx_display_name (display_name),
    INDEX idx_weapon_id (weapon_id),
    INDEX idx_armor_id (armor_id),
    INDEX idx_shield_id (shield_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

