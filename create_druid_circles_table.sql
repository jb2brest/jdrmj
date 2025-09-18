-- Table pour les cercles druidiques
CREATE TABLE IF NOT EXISTS druid_circles (
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

-- Table pour les choix de cercle druidique des personnages
CREATE TABLE IF NOT EXISTS character_druid_circle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    circle_id INT NOT NULL,
    level_2_choice VARCHAR(255) NULL,
    level_6_choice VARCHAR(255) NULL,
    level_10_choice VARCHAR(255) NULL,
    level_14_choice VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (circle_id) REFERENCES druid_circles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_circle (character_id)
);
