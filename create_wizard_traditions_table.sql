-- Table pour les traditions arcaniques
CREATE TABLE IF NOT EXISTS wizard_traditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    level_2_feature TEXT,
    level_6_feature TEXT,
    level_10_feature TEXT,
    level_14_feature TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table pour les choix de tradition arcanique des personnages
CREATE TABLE IF NOT EXISTS character_wizard_tradition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    tradition_id INT NOT NULL,
    level_2_choice VARCHAR(255) NULL,
    level_6_choice VARCHAR(255) NULL,
    level_10_choice VARCHAR(255) NULL,
    level_14_choice VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (tradition_id) REFERENCES wizard_traditions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_tradition (character_id)
);
