-- Table pour les faveurs de pacte
CREATE TABLE IF NOT EXISTS warlock_pacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    level_3_feature TEXT,
    level_7_feature TEXT,
    level_15_feature TEXT,
    level_20_feature TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table pour les choix de faveur de pacte des personnages
CREATE TABLE IF NOT EXISTS character_warlock_pact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    pact_id INT NOT NULL,
    level_3_choice VARCHAR(255) NULL,
    level_7_choice VARCHAR(255) NULL,
    level_15_choice VARCHAR(255) NULL,
    level_20_choice VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (pact_id) REFERENCES warlock_pacts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_pact (character_id)
);
