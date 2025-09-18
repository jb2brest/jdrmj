-- Table pour les origines magiques
CREATE TABLE IF NOT EXISTS sorcerer_origins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    level_1_feature TEXT,
    level_6_feature TEXT,
    level_14_feature TEXT,
    level_18_feature TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table pour les choix d'origine magique des personnages
CREATE TABLE IF NOT EXISTS character_sorcerer_origin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    origin_id INT NOT NULL,
    level_1_choice VARCHAR(255) NULL,
    level_6_choice VARCHAR(255) NULL,
    level_14_choice VARCHAR(255) NULL,
    level_18_choice VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (origin_id) REFERENCES sorcerer_origins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_origin (character_id)
);
