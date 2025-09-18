-- Table pour les traditions monastiques
CREATE TABLE IF NOT EXISTS monk_traditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    level_3_feature TEXT,
    level_6_feature TEXT,
    level_11_feature TEXT,
    level_17_feature TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table pour les choix de tradition monastique des personnages
CREATE TABLE IF NOT EXISTS character_monk_tradition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    tradition_id INT NOT NULL,
    level_3_choice VARCHAR(255) NULL,
    level_6_choice VARCHAR(255) NULL,
    level_11_choice VARCHAR(255) NULL,
    level_17_choice VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (tradition_id) REFERENCES monk_traditions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_tradition (character_id)
);
