-- Tables temporaires pour la création de personnages (PJ et PNJ)
-- Préfixe PT_ (Personnage Temporaire)

-- Table principale des personnages temporaires
CREATE TABLE IF NOT EXISTS PT_characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    character_type ENUM('player', 'npc') NOT NULL DEFAULT 'player',
    step INT NOT NULL DEFAULT 1,
    class_id INT,
    race_id INT,
    background_id INT,
    name VARCHAR(100),
    level INT DEFAULT 1,
    experience INT DEFAULT 0,
    alignment VARCHAR(50),
    personality_traits TEXT,
    ideals TEXT,
    bonds TEXT,
    flaws TEXT,
    backstory TEXT,
    age INT,
    height VARCHAR(20),
    weight VARCHAR(20),
    eyes VARCHAR(50),
    skin VARCHAR(50),
    hair VARCHAR(50),
    profile_photo VARCHAR(255),
    strength INT DEFAULT 8,
    dexterity INT DEFAULT 8,
    constitution INT DEFAULT 8,
    intelligence INT DEFAULT 8,
    wisdom INT DEFAULT 8,
    charisma INT DEFAULT 8,
    hit_points_max INT DEFAULT 8,
    hit_points_current INT DEFAULT 8,
    armor_class INT DEFAULT 10,
    speed INT DEFAULT 30,
    proficiency_bonus INT DEFAULT 2,
    gold INT DEFAULT 0,
    silver INT DEFAULT 0,
    copper INT DEFAULT 0,
    selected_skills JSON,
    selected_languages JSON,
    selected_equipment JSON,
    is_equipped BOOLEAN DEFAULT FALSE,
    equipment_locked BOOLEAN DEFAULT FALSE,
    character_locked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE SET NULL,
    FOREIGN KEY (background_id) REFERENCES backgrounds(id) ON DELETE SET NULL
);

-- Table des choix d'équipement temporaires
CREATE TABLE IF NOT EXISTS PT_equipment_choices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pt_character_id INT NOT NULL,
    choice_type ENUM('class', 'background', 'race') NOT NULL,
    choice_index INT NOT NULL,
    selected_option VARCHAR(10) NOT NULL,
    selected_weapons JSON,
    selected_instruments JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pt_character_id) REFERENCES PT_characters(id) ON DELETE CASCADE
);

-- Table des capacités temporaires
CREATE TABLE IF NOT EXISTS PT_capabilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pt_character_id INT NOT NULL,
    capability_name VARCHAR(100) NOT NULL,
    capability_description TEXT,
    capability_type VARCHAR(50),
    level_acquired INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pt_character_id) REFERENCES PT_characters(id) ON DELETE CASCADE
);

-- Index pour optimiser les performances
CREATE INDEX idx_pt_characters_user_id ON PT_characters(user_id);
CREATE INDEX idx_pt_characters_type ON PT_characters(character_type);
CREATE INDEX idx_pt_characters_step ON PT_characters(step);
CREATE INDEX idx_pt_equipment_character_id ON PT_equipment_choices(pt_character_id);
CREATE INDEX idx_pt_capabilities_character_id ON PT_capabilities(pt_character_id);
