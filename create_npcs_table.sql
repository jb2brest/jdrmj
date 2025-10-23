-- Création de la table npcs pour les PNJ
CREATE TABLE IF NOT EXISTS npcs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    class_id INT NOT NULL,
    race_id INT NOT NULL,
    background_id INT,
    archetype_id INT,
    level INT DEFAULT 1,
    experience INT DEFAULT 0,
    
    -- Caractéristiques
    strength INT DEFAULT 10,
    dexterity INT DEFAULT 10,
    constitution INT DEFAULT 10,
    intelligence INT DEFAULT 10,
    wisdom INT DEFAULT 10,
    charisma INT DEFAULT 10,
    
    -- Statistiques calculées
    hit_points INT DEFAULT 8,
    armor_class INT DEFAULT 10,
    speed INT DEFAULT 30,
    
    -- Informations personnelles
    alignment VARCHAR(50) DEFAULT 'Neutre Neutre',
    age VARCHAR(50),
    height VARCHAR(50),
    weight VARCHAR(50),
    eyes VARCHAR(50),
    skin VARCHAR(50),
    hair VARCHAR(50),
    
    -- Histoire et personnalité
    backstory TEXT,
    personality_traits TEXT,
    ideals TEXT,
    bonds TEXT,
    flaws TEXT,
    
    -- Équipement et sorts
    starting_equipment TEXT,
    gold INT DEFAULT 0,
    spells TEXT,
    skills TEXT,
    languages TEXT,
    
    -- Photo de profil
    profile_photo VARCHAR(500),
    
    -- Métadonnées
    created_by INT NOT NULL,
    world_id INT NOT NULL,
    location_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Clés étrangères
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
    FOREIGN KEY (background_id) REFERENCES backgrounds(id) ON DELETE SET NULL,
    FOREIGN KEY (archetype_id) REFERENCES class_archetypes(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (world_id) REFERENCES worlds(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES places(id) ON DELETE SET NULL,
    
    -- Index pour les performances
    INDEX idx_npcs_created_by (created_by),
    INDEX idx_npcs_world_id (world_id),
    INDEX idx_npcs_location_id (location_id),
    INDEX idx_npcs_class_id (class_id),
    INDEX idx_npcs_race_id (race_id),
    INDEX idx_npcs_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
