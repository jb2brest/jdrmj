-- Table pour les archétypes martiaux
CREATE TABLE IF NOT EXISTS fighter_archetypes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    level_3_feature TEXT,
    level_7_feature TEXT,
    level_10_feature TEXT,
    level_15_feature TEXT,
    level_18_feature TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table pour les choix d'archétype martial des personnages
CREATE TABLE IF NOT EXISTS character_fighter_archetype (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    archetype_id INT NOT NULL,
    level_3_choice VARCHAR(255) NULL,
    level_7_choice VARCHAR(255) NULL,
    level_10_choice VARCHAR(255) NULL,
    level_15_choice VARCHAR(255) NULL,
    level_18_choice VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (archetype_id) REFERENCES fighter_archetypes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_archetype (character_id)
);
