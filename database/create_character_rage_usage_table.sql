-- Création de la table character_rage_usage pour gérer l'utilisation de la rage des barbares
CREATE TABLE IF NOT EXISTS character_rage_usage (
    character_id INT PRIMARY KEY,
    used INT DEFAULT 0,
    max_uses INT DEFAULT 2,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);
