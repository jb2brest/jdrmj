-- Table pour les domaines divins
CREATE TABLE IF NOT EXISTS cleric_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    level_1_feature TEXT,
    level_2_feature TEXT,
    level_6_feature TEXT,
    level_8_feature TEXT,
    level_17_feature TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table pour les choix de domaine divin des personnages
CREATE TABLE IF NOT EXISTS character_cleric_domain (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    domain_id INT NOT NULL,
    level_1_choice VARCHAR(255) NULL,
    level_2_choice VARCHAR(255) NULL,
    level_6_choice VARCHAR(255) NULL,
    level_8_choice VARCHAR(255) NULL,
    level_17_choice VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (domain_id) REFERENCES cleric_domains(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_domain (character_id)
);
