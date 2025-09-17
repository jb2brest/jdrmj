-- Création de la table des historiques
CREATE TABLE IF NOT EXISTS backgrounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    skill_proficiencies TEXT, -- JSON array des compétences maîtrisées
    tool_proficiencies TEXT,  -- JSON array des outils maîtrisés
    languages TEXT,           -- JSON array des langues
    equipment TEXT,           -- Description de l'équipement
    feature VARCHAR(255),     -- Nom de la capacité spéciale
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Ajouter la colonne background_id à la table characters
ALTER TABLE characters ADD COLUMN background_id INT DEFAULT NULL;
ALTER TABLE characters ADD FOREIGN KEY (background_id) REFERENCES backgrounds(id);

